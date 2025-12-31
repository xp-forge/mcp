<?php namespace io\modelcontextprotocol\server;

use lang\reflection\{Type, TargetException};
use util\Bytes;

/** Base class for InstanceDelegate, Delegates and ImplementationsIn */ 
abstract class Delegate {

  /** @param object|array|string|io.modelcontextprotocol.server.Delegate $arg */
  public static function from($arg): self {
    if ($arg instanceof self) {
      return $arg;
    } else if (is_array($arg)) {
      return new Delegates($arg);
    } else if (is_string($arg)) {
      return new ImplementationsIn($arg);
    } else {
      return new InstanceDelegate($arg);
    }
  }

  /**
   * Finds a readable resource
   *
   * @param  string $tool
   * @return ?[:var]
   */
  public abstract function readable($uri);

  /**
   * Finds an invokeable tool
   *
   * @param  string $tool
   * @return ?function(var[]): var
   */
  public abstract function invokeable($tool);

  /** Yields all tools in a given type */
  protected function toolsIn(Type $type, string $namespace): iterable {
    foreach ($type->methods()->annotated(Tool::class) as $name => $method) {
      $properties= $required= [];
      foreach ($method->parameters() as $param => $reflect) {
        $annotations= $reflect->annotations();
        if ($annotations->provides(Value::class)) {
          continue;
        } else if ($annotation= $annotations->type(Param::class)) {
          $properties[$param]= $annotation->newInstance()->schema();
        } else {
          $properties[$param]= ['type' => 'string', 'description' => ucfirst($param)];
        }
        $reflect->optional() || $required[]= $param;
      }

      yield [
        'name'        => $namespace.'_'.$name,
        'description' => $method->comment() ?? ucfirst($name).' '.$namespace,
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
        $annotations= $reflect->annotations();
        if ($annotations->provides(Value::class)) {
          continue;
        } else if ($annotation= $annotations->type(Param::class)) {
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
        'description' => $method->comment() ?? ucfirst($name).' '.$namespace,
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

  /** Returns contents of a given resource */
  protected function contentsOf(string $uri, string $mimeType, $result) {
    return [
      ['uri' => $uri, 'mimeType' => $mimeType] + ($result instanceof Bytes
        ? ['blob' => base64_encode($result)]
        : ['text' => $result]
      )
    ];
  }

  /** Access a given method */
  protected function access($instance, $method, $arguments, $request) {
    $pass= [];
    $values= null;
    foreach ($method->parameters() as $param => $reflect) {
      $annotations= $reflect->annotations();
      if ($annotations->provides(Param::class)) {
        $pass[]= $arguments[$param] ?? $reflect->default();
      } else if ($value= $annotations->type(Value::class)) {
        $values??= $request->values();
        $pass[]= $values[$value->argument(0) ?? $param] ?? $reflect->default();
      } else {
        $values??= $request->values();
        $pass[]= $values['segments'][$param] ?? $reflect->default();
      }
    }

    try {
      return $method->invoke($instance, $pass);
    } catch (TargetException $e) {
      throw $e->getCause();
    }
  }
}