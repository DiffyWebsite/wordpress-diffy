<?php

namespace Diffy;

class Key
{
    /**
     * Create API Key.
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws InvalidArgumentsException
     */
    public static function create(string $name)
    {
        if (empty($name)) {
            throw new InvalidArgumentsException('Name can not be empty');
        }

        return Diffy::request('POST', 'user/keys', ['name' => $name]);
    }
}
