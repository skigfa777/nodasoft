<?php

namespace Manager;

use \Gateway\User as Gateway;

class User
{
    const limit = 10;

    /**
     * Возвращает пользователей старше заданного возраста.
     * @param int $ageFrom
     * @return array
     */
    function getUsers(int $ageFrom): array
    {
        $ageFrom = (int)trim($ageFrom);
        return Gateway::getOlderByAge($ageFrom);
    }

    /**
     * Возвращает пользователей по списку имен.
     * @return array
     */
    public static function getByNames(): array
    {
        $users = [];
        //$_GET['names'] - так нежелательно, надо отфильтровать
        $names = filter_input(INPUT_GET, 'names', FILTER_SANITIZE_STRING);
        if ($names) {
            foreach ($names as $name) {
                $users[] = Gateway::getByName($name);//SQL-запрос в цикле, вероятно не всегда хорошо
            }
        }
        return $users;
    }

    /**
     * Добавляет пользователей в базу данных.
     * @param array $users
     * @return array
     */
    public function add(array $users): array
    {
        $ids = [];
        //транзакции, возможно и здесь что-то надо подрихтовать 
        \Gateway\User::getInstance()->beginTransaction();
        foreach ($users as $user) {
            try {
                Gateway::add($user['name'], $user['lastName'], $user['age']);
                Gateway::getInstance()->commit();
                $ids[] = Gateway::getInstance()->lastInsertId();
            } catch (\Exception $e) {
                Gateway::getInstance()->rollBack();
            }
        }
        return $ids;
    }
}