<?php

namespace GlpiPlugin\Consumables;

use CommonDBTM;

class Option extends CommonDBTM {
    public static function getTypeName($n = 0): string { return 'Option'; }
    public static function getTable() { return ''; }
}
