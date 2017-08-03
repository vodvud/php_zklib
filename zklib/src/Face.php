<?php

namespace ZK;

use ZKLib;

class Face
{
    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function on(ZKLib $self)
    {
        $command = Util::CMD_DEVICE;
        $command_string = 'FaceFunOn';

        return $self->_command($command, $command_string);
    }
}

