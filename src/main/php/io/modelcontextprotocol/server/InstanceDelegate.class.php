<?php namespace io\modelcontextprotocol\server;

use lang\{Reflection, MethodNotImplementedException};

class InstanceDelegate extends Delegates {
  private $instance, $type, $namespace;

  public function __construct(object $instance, ?string $namespace= null) {
    $this->instance= $instance;
    $this->type= Reflection::type($instance);
    $this->namespace= $namespace ?? strtolower($this->type->declaredName());
  }

  public function invoke($tool, $arguments) {
    sscanf($tool, $this->namespace.'_%s', $method);
    if ($reflect= $this->type->method($method)) {
      return $reflect->invoke($this->instance, (array)$arguments);
    }

    throw new MethodNotImplementedException($method);
  }

  public function tools(): iterable {
    foreach ($this->type->methods() as $name => $method) {
      if ($method->annotation(Tool::class)) {

        // Use annotated parameters if possible
        $properties= $required= [];
        foreach ($method->parameters() as $param => $reflect) {
          $annotations= $reflect->annotations();
          if ($annotation= $annotations->type(Param::class)) {
            $properties[$param]= $annotation->newInstance()->schema();
          } else {
            $properties[$param]= ['type' => 'string', 'description' => ucfirst($param)];
          }
          $reflect->optional() || $required[]= $param;
        }

        yield [
          'name'        => $this->namespace.'_'.$name,
          'description' => $method->comment() ?? null,
          'inputSchema' => [
            'type'       => 'object',
            'properties' => $properties ?: (object)[],
            'required'   => [],
          ],
        ];
      }
    }
  }
}