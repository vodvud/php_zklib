<?php

namespace ZK;

use ZKLib;

class Os
{
    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function get(ZKLib $self)
    {
        $command = Util::CMD_DEVICE;
        $command_string = '~OS';

        return $self->_command($command, $command_string);
    }
}