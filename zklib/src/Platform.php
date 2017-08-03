<?php

namespace ZK;

use ZKLib;

class Platform
{
    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function get(ZKLib $self)
    {
        $command = Util::CMD_DEVICE;
        $command_string = '~Platform';

        return $self->_command($command, $command_string);
    }

    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function getVersion(ZKLib $self)
    {
        $command = Util::CMD_DEVICE;
        $command_string = '~ZKFPVersion';

        return $self->_command($command, $command_string);
    }
}