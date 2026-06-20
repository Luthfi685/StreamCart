<?php

class Util {
    public static function packUInt16($v) {
        return pack("v", $v);
    }

    public static function packInt32($v) {
        return pack("V", $v);
    }

    public static function packUInt32($v) {
        return pack("V", $v);
    }

    public static function packString($v) {
        return self::packUInt16(strlen($v)) . $v;
    }

    public static function packMapUInt32($m) {
        $result = self::packUInt32(count($m));
        foreach ($m as $key => $value) {
            $result .= self::packUInt32($key) . self::packUInt32($value);
        }
        return $result;
    }

    public static function unpackUInt16(&$data) {
        $v = unpack("v", substr($data, 0, 2));
        $data = substr($data, 2);
        return $v[1];
    }

    public static function unpackUInt32(&$data) {
        $v = unpack("V", substr($data, 0, 4));
        $data = substr($data, 4);
        return $v[1];
    }

    public static function unpackInt32(&$data) {
        $v = unpack("V", substr($data, 0, 4));
        $data = substr($data, 4);
        $result = $v[1];
        if ($result >= 0x80000000) {
            $result -= 0x100000000;
        }
        return $result;
    }

    public static function unpackString(&$data) {
        $len = self::unpackUInt16($data);
        $v = substr($data, 0, $len);
        $data = substr($data, $len);
        return $v;
    }

    public static function unpackMapUInt32(&$data) {
        $m = [];
        $count = self::unpackUInt32($data);
        for ($i = 0; $i < $count; $i++) {
            $key = self::unpackUInt32($data);
            $value = self::unpackUInt32($data);
            $m[$key] = $value;
        }
        return $m;
    }
}
