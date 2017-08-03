<?php

namespace ZK;

use ZKLib;

class Device
{
    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function name(ZKLib $self)
    {
        $command = Util::CMD_DEVICE;
        $command_string = '~DeviceName';

        return $self->_command($command, $command_string);
    }

    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function enable(ZKLib $self)
    {
        $command = Util::CMD_ENABLE_DEVICE;
        $command_string = '';

        return $self->_command($command, $command_string);
    }

    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function disable(ZKLib $self)
    {
        $command = Util::CMD_DISABLE_DEVICE;
        $command_string = chr(0) . chr(0);

        return $self->_command($command, $command_string);
    }
}