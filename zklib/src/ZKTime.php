<?php

class ZKTime
{
    /**
     * @param ZKLib $self
     * @param string $t Format: "Y-m-d H:i:s"
     * @return bool|mixed
     */
    public function set(ZKLib $self, $t)
    {
        $command = ZKConst::CMD_SET_TIME;
        $command_string = pack('I', ZKConst::encode_time($t));

        return $self->_command($command, $command_string);
    }

    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function get(ZKLib $self)
    {
        $command = ZKConst::CMD_GET_TIME;
        $command_string = '';

        $ret = $self->_command($command, $command_string);

        if ($ret) {
            return ZKConst::decode_time(hexdec(ZKConst::reverseHex(bin2hex($ret))));
        } else {
            return false;
        }
    }
}