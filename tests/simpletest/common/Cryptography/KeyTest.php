<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Cryptography;

class KeyTest extends \TuleapTestCase
{
    public function itRetrievesRawKeyMaterial()
    {
        $key_material = 'key_material';
        $key          = new Key(new ConcealedString($key_material));

        $this->assertEqual($key_material, $key->getRawKeyMaterial());
    }

    public function itDoesNotSerialize()
    {
        $key = new Key(new ConcealedString('key_material'));

        $this->expectException('Tuleap\\Cryptography\\Exception\\CannotSerializeKeyException');
        serialize($key);
    }

    public function itDoesNotUnserialize()
    {
        $this->expectException('Tuleap\\Cryptography\\Exception\\CannotSerializeKeyException');
        unserialize('O:23:"Tuleap\Cryptography\Key":0:{}');
    }

    public function itIsNotTransformedToAString()
    {
        $key = new Key(new ConcealedString('key_material'));

        $this->assertEqual('', (string) $key);
    }
}
