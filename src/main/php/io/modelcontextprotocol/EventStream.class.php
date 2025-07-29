<?php namespace io\modelcontextprotocol;

use Traversable;
use io\streams\{InputStream, StringReader};

class EventStream extends Result {
  private $stream;

  /** Creates a new event stream */
  public function __construct(InputStream $stream) {
    $this->stream= $stream;
  }

  /** Returns events while reading */
  public function getIterator(): Traversable {
    $r= new StringReader($this->stream);
    $event= null;

    // Read all lines starting with `event` or `data`, ignore others
    while (null !== ($line= $r->readLine())) {
      // echo "\n<<< $line\n";
      if (0 === strncmp($line, 'event: ', 6)) {
        $event= substr($line, 7);
      } else if (0 === strncmp($line, 'data: ', 5)) {
        yield $event => Result::from(json_decode(substr($line, 6), true));
        $event= null;
      }
    }
  }

  /** Returns the first message */
  public function value() {
    $it= $this->getIterator();
    return $it->valid() ? $it->current()->value() : null;
  }
}