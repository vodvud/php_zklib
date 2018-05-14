<?php

namespace ZK;

use ZKLib;

class Fingerprint
{
    /**
     * @param ZKLib $self
     * @param integer $uid Unique Employee ID in ZK device
     * @return array Binary fingerprint data array
     */
    public function get(ZKLib $self, $uid)
    {
        $self->_section = __METHOD__;

        $data = [];
        //fingers of the hands
        for ($i = 0; $i <= 9; $i++) {
            $tmp = $this->_getFinger($self, $uid, $i);
            if ($tmp['size'] > 0) {
                $data[$i] = $tmp['tpl'];
            }
            unset($tmp);
        }
        return $data;
    }


    /**
     * @param ZKLib $self
     * @param integer $uid Unique Employee ID in ZK device
     * @param integer $finger Finger ID (0-9)
     * @return array
     */
    public function _getFinger(ZKLib $self, $uid, $finger)
    {
        $command = Util::CMD_USER_TEMP_RRQ;
        $byte1 = chr((int)($uid % 256));
        $byte2 = chr((int)($uid >> 8));
        $command_string = $byte1 . $byte2 . chr($finger);

        $ret = [
            'size' => 0,
            'tpl' => ''
        ];

        $session_id = $self->_command($command, $command_string, Util::COMMAND_TYPE_DATA);

        if ($session_id === false) {
            return $ret;
        }

        $self->_session_id = $session_id;
        $data = Util::recData($self, 10, false);

        if (!empty($data)) {
            $templateSize = strlen($data) + 6;
            $prefix = chr($templateSize % 256) . chr(round($templateSize / 256)) . $byte1 . $byte2 . chr($finger) . chr(1);
            $data = $prefix . $data;
            if (strlen($data) > 6) {
                $ret['size'] = $templateSize;
                $ret['tpl'] = $data;
            }
        }

        return $ret;
    }

    /**
     * TODO: Still return false. I need more documentation about it, or play with data and try.
     *
     * @param ZKLib $self
     * @param array $data Binary fingerprint data array (same like returned array from 'get' method)
     * @return bool
     */
    public function set(ZKLib $self, array $data)
    {
        $self->_section = __METHOD__;

        try {
            foreach ($data as $item) {
                if ($this->_setFinger($self, $item) === false) {
                    return false;
                }
            }
            return true;
        } catch (\ErrorException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param ZKLib $self
     * @param string $item Binary fingerprint data item
     * @return bool|mixed
     */
    private function _setFinger(ZKLib $self, $item)
    {
        $command = Util::CMD_USER_TEMP_WRQ;
        $command_string = $item;

        return $self->_command($command, $command_string);
    }

    /**
     * @param ZKLib $self
     * @param int $uid Unique Employee ID in ZK device
     * @param array $data Fingers ID array (0-9)
     * @return bool
     */
    public function remove(ZKLib $self, $uid, array $data)
    {
        $self->_section = __METHOD__;

        $byte1 = chr((int)($uid % 256));
        $byte2 = chr((int)($uid >> 8));

        foreach ($data as $finger) {
            if ($this->_removeFinger($self, $byte1, $byte2, $finger) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param ZKLib $self
     * @param string $byte1
     * @param string $byte2
     * @param int $finger Finger ID (0-9)
     * @return bool|mixed
     */
    private function _removeFinger(ZKLib $self, $byte1, $byte2, $finger)
    {
        $command = Util::CMD_DELETE_USER_TEMP;
        $command_string = ($byte1 . $byte2) . chr($finger);

        return $self->_command($command, $command_string);
    }

}