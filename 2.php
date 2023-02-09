<?php

namespace Gateway;

use PDO;

class User
{
    /**
     * @var PDO
     */
    public static $instance;

    /**
     * Реализация singleton
     * @return PDO
     */
    public static function getInstance(): PDO
    {
        if (is_null(self::$instance)) {
            $dsn = 'mysql:dbname=db;host=127.0.0.1';
            $user = 'dbuser';
            $password = 'dbpass';
            self::$instance = new PDO($dsn, $user, $password);
        }

        return self::$instance;
    }

    /**
     * Возвращает список пользователей старше заданного возраста.
     * @param int $ageFrom
     * @return array
     */
    public static function getUsers(int $ageFrom): array
    {
        $stmt = self::getInstance()->prepare("SELECT id, name, lastName, from, age, settings FROM Users WHERE age > {$ageFrom} LIMIT " . \Manager\User::limit);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $users = [];
        foreach ($rows as $row) {
            $settings = json_decode($row['settings']);
            $users[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'lastName' => $row['lastName'],
                'from' => $row['from'],
                'age' => $row['age'],
                'key' => $settings['key'],
            ];
        }

        return $users;
    }

    /**
     * Возвращает пользователя по имени.
     * @param string $name
     * @return array || boolean
     */
    public static function getByName(string $name): array
    {
        //1) исправляем ошибки в SQL-запросе, from - зарезервированное слово, так что оборачиваем в ``:
        $stmt = self::getInstance()->prepare("SELECT `id`, `name`, `lastName`, `from`, `age`, `settings` FROM Users WHERE name = :name");
        //2) вот тут точно не вспомню, но суть в том, что перед отправой запроса входные значения надо обезвредить:
        $stmt->bindValue(':name', (string) $name, self::getInstance()::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Добавляет пользователя в базу данных.
     * @param string $name
     * @param string $lastName
     * @param int $age
     * @return string
     */
    public static function add(string $name, string $lastName, int $age): string
    {
        //1) если раннее PDO был назван, как $stmt, то и в остальных методах он также должен быть назван, как $stmt, а не как вздумается
        //2) далее, также обезвреживаем входные данные перед отправкой запроса, как и в методе getByName()
        //3) в SQL-запросе перепутаны поля, т.е. в поле lastName войдет age, исправим, правильный порядок Имя, Фамилия, Возраст
        $stmt = self::getInstance()->prepare("INSERT INTO Users (name, lastName, age) VALUES (:name, :lastName, :age)");
        $stmt->bindValue(':name', (string) $name, self::getInstance()::PARAM_STR);
        $stmt->bindValue(':lastName', (string) $lastName, self::getInstance()::PARAM_STR);
        $stmt->bindValue(':age', (int) $age, self::getInstance()::PARAM_INT);
        $stmt->execute();
        return self::getInstance()->lastInsertId();
    }
}