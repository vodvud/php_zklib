<?php

namespace ZK;

use ZKLib;

class Ssr
{
    /**
     * @param ZKLib $self
     * @return bool|mixed
     */
    public function get(ZKLib $self)
    {
        $command = Util::CMD_DEVICE;
        $command_string = '~SSR';

        return $self->_command($command, $command_string);
    }
}