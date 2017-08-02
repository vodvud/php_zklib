<?php

class ZKPin
{
    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function width(ZKLib $self)
    {
        $command = ZKConst::CMD_DEVICE;
        $command_string = '~PIN2Width';

        return $self->_command($command, $command_string);
    }
}