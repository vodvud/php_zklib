<?php

namespace ZK;

use ZKLib;

class WorkCode
{
    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function get(ZKLib $self)
    {
        $command = Util::CMD_DEVICE;
        $command_string = 'WorkCode';

        return $self->_command($command, $command_string);
    }
}
