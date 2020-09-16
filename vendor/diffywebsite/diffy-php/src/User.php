<?php

namespace Diffy;

class User
{

  /**
   * Create User.
   *
   * @param string $name
   * @param string $email
   * @param string $password
   * @param string $location
   *   Used internally for tracking where user is coming from, utm_* tags.
   * @return mixed
   * @throws \Diffy\InvalidArgumentsException
   */
  public static function create(string $name, string $email, string $password, string $location = '')
  {
    if (empty($name)) {
      throw new InvalidArgumentsException('Name can not be empty');
    }
    if (empty($email)) {
      throw new InvalidArgumentsException('Email can not be empty');
    }
    if (empty($password)) {
      throw new InvalidArgumentsException('Password can not be empty');
    }

    return Diffy::request(
      'POST',
      'register',
      [
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'location' => $location,
      ]
    );
  }

}
