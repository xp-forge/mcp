<?php namespace io\modelcontextprotocol\server;

use lang\reflection\Type;

abstract class Delegates {

  protected function toolsIn(Type $type, string $namespace): iterable {
    foreach ($type->methods() as $name => $method) {
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
          'name'        => $namespace.'_'.$name,
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