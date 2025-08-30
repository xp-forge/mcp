<?php namespace io\modelcontextprotocol\server;

use lang\reflection\Type;
use util\Bytes;

abstract class Delegates {

  /** Yields all tools in a given type */
  protected function toolsIn(Type $type, string $namespace): iterable {
    foreach ($type->methods()->annotated(Tool::class) as $name => $method) {
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
          'required'   => $required,
        ],
      ];
    }
  }

  /** Yields all prompts in a given type */
  protected function promptsIn(Type $type, string $namespace): iterable {
    foreach ($type->methods()->annotated(Prompt::class) as $name => $method) {
      $arguments= [];
      foreach ($method->parameters() as $param => $reflect) {
        if ($annotation= $reflect->annotation(Param::class)) {
          $schema= $annotation->newInstance()->schema();
          $description= $schema['description'] ?? null;
          unset($schema['description']);
        } else {
          $schema= [];
          $description= null;
        }
        $arguments[]= [
          'name'        => $param,
          'description' => $description ?? ucfirst($param),
          'required'    => !$reflect->optional(),
          'schema'      => $schema ?? ['type' => 'string'],
        ];
      }

      yield [
        'name'        => $namespace.'_'.$name,
        'description' => $method->comment() ?? null,
        'arguments'   => $arguments,
      ];
    }
  }

  /** Yields all resources in a given type */
  protected function resourcesIn(Type $type, string $namespace, bool $templates): iterable {
    foreach ($type->methods() as $name => $method) {
      if ($annotation= $method->annotation(Resource::class)) {
        $resource= $annotation->newInstance();
        $templates === $resource->template && yield $resource->meta + [
          'name'        => $namespace.'_'.$name,
          'description' => $method->comment() ?? null,
        ];
      }
    }
  }

  protected function contentsOf(string $uri, string $mimeType, $result) {
    return [
      ['uri' => $uri, 'mimeType' => $mimeType] + ($result instanceof Bytes
        ? ['blob' => base64_encode($result)]
        : ['text' => $result]
      )
    ];
  }
}