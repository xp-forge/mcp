<?php namespace io\modelcontextprotocol;

use Traversable;
use io\streams\{InputStream, StringReader};
use text\json\StringInput;
use util\Objects;

/** @test io.modelcontextprotocol.unittest.EventStreamTest */
class EventStream extends Outcome {
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
        yield $event => Outcome::from((new StringInput(substr($line, 6)))->read());
        $event= null;
      }
    }
  }

  /** Returns the first message */
  public function value() {
    $it= $this->getIterator();
    return $it->valid() ? $it->current()->value() : null;
  }

  /** @return string */
  public function toString() { return nameof($this).'('.Objects::stringOf($this->stream).')'; }

  /** @return string */
  public function hashCode() { return 'S'.Objects::hashOf($this->stream); }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self
      ? Objects::compare($this->stream, $value->stream)
      : 1
    ;
  }
}