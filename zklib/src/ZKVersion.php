<?php

class ZKVersion
{
    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function get(ZKLib $self)
    {
        $command = ZKConst::CMD_VERSION;
        $command_string = '';

        return $self->_command($command, $command_string);
    }
}