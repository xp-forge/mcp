<?php namespace io\modelcontextprotocol\server;

use lang\{Reflection, MethodNotImplementedException};

class InstanceDelegate {
  private $instance;

  public function __construct(object $instance) {
    $this->instance= $instance;
  }

  public function invoke($method, $arguments) {
    if ($reflect= Reflection::type($this->instance)->method($method)) {
      return $reflect->invoke($this->instance, (array)$arguments);
    }

    throw new MethodNotImplementedException($method);
  }

  public function tools() {
    $tools= [];
    foreach (Reflection::type($this->instance)->methods() as $name => $method) {
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

        $tools[]= [
          'name'        => $name,
          'description' => $method->comment() ?? null,
          'inputSchema' => [
            'type'       => 'object',
            'properties' => $properties ?: (object)[],
            'required'   => [],
          ],
        ];
      }
    }
    return ['tools' => $tools];
  }
}