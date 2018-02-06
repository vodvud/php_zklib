<?php

namespace ZK;

use ZKLib;

class Pin
{
    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function width(ZKLib $self)
    {
        $self->_section = __METHOD__;

        $command = Util::CMD_DEVICE;
        $command_string = '~PIN2Width';

        return $self->_command($command, $command_string);
    }
}