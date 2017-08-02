<?php

namespace ZK;

use ZKLib;

class Constant
{
    const USHRT_MAX = 65535;

    const CMD_CONNECT = 1000;
    const CMD_EXIT = 1001;
    const CMD_ENABLE_DEVICE = 1002;
    const CMD_DISABLE_DEVICE = 1003;

    const CMD_ACK_OK = 2000;
    const CMD_ACK_ERROR = 2001;
    const CMD_ACK_DATA = 2002;

    const CMD_PREPARE_DATA = 1500;
    const CMD_DATA = 1501;

    const CMD_USER_TEMP_RRQ = 9;
    const CMD_ATT_LOG_RRQ = 13;
    const CMD_CLEAR_DATA = 14;
    const CMD_CLEAR_ATT_LOG = 15;

    const CMD_WRITE_LCD = 66;

    const CMD_GET_TIME = 201;
    const CMD_SET_TIME = 202;

    const CMD_VERSION = 1100;
    const CMD_DEVICE = 11;

    const CMD_CLEAR_ADMIN = 20;
    const CMD_SET_USER = 8;

    const LEVEL_USER = 0;
    const LEVEL_ADMIN = 14;

    const COMMAND_TYPE_GENERAL = 'general';
    const COMMAND_TYPE_DATA = 'data';

    /**
     * Encode a timestamp send at the timeclock
     * copied from zkemsdk.c - EncodeTime
     *
     * @param string $t Format: "Y-m-d H:i:s"
     * @return int
     */
    static public function encode_time($t)
    {
        $timestamp = strtotime($t);
        $t = (object)[
            'year' => (int)date('Y', $timestamp),
            'month' => (int)date('m', $timestamp),
            'day' => (int)date('d', $timestamp),
            'hour' => (int)date('H', $timestamp),
            'minute' => (int)date('i', $timestamp),
            'second' => (int)date('s', $timestamp),
        ];

        $d = (($t->year % 100) * 12 * 31 + (($t->month - 1) * 31) + $t->day - 1) *
            (24 * 60 * 60) + ($t->hour * 60 + $t->minute) * 60 + $t->second;

        return $d;
    }

    /**
     * Decode a timestamp retrieved from the timeclock
     * copied from zkemsdk.c - DecodeTime
     *
     * @param int|string $t
     * @return false|string Format: "Y-m-d H:i:s"
     */
    static public function decode_time($t)
    {
        $second = $t % 60;
        $t = $t / 60;

        $minute = $t % 60;
        $t = $t / 60;

        $hour = $t % 24;
        $t = $t / 24;

        $day = $t % 31 + 1;
        $t = $t / 31;

        $month = $t % 12 + 1;
        $t = $t / 12;

        $year = floor($t + 2000);

        $d = date('Y-m-d H:i:s', strtotime(
            $year . '-' . $month . '-' . $day . ' ' . $hour . ':' . $minute . ':' . $second
        ));

        return $d;
    }

    /**
     * @param string $hex
     * @return string
     */
    static public function reverseHex($hex)
    {
        $tmp = '';

        for ($i = strlen($hex); $i >= 0; $i--) {
            $tmp .= substr($hex, $i, 2);
            $i--;
        }

        return $tmp;
    }

    /**
     * Checks a returned packet to see if it returned self::CMD_PREPARE_DATA,
     * indicating that data packets are to be sent
     * Returns the amount of bytes that are going to be sent
     *
     * @param ZKLib $self
     * @return bool|number
     */
    static public function getSize(ZKLib $self)
    {
        $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr($self->_data_recv, 0, 8));
        $command = hexdec($u['h2'] . $u['h1']);

        if ($command == self::CMD_PREPARE_DATA) {
            $u = unpack('H2h1/H2h2/H2h3/H2h4', substr($self->_data_recv, 8, 4));
            $size = hexdec($u['h4'] . $u['h3'] . $u['h2'] . $u['h1']);
            return $size;
        } else {
            return false;
        }
    }
}