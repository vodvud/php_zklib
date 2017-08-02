<?php

class ZKFace
{
    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function on(ZKLib $self)
    {
        $command = ZKConst::CMD_DEVICE;
        $command_string = 'FaceFunOn';

        return $self->_command($command, $command_string);
    }
}

