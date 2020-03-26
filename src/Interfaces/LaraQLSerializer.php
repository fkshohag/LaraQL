<?php

namespace Shohag\Interfaces;

/**
 * @author Fazlul Kabir Shohag <shohag.fks@gmail.com>
 */

Interface LaraQLSerializer {
      public function serializerFields(): array;
      public function postSerializerFields(): array;
}

?>