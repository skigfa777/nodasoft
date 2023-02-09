<?php

namespace Gateway;

use PDO;
use \Manager\User as Manager;

class User
{
    //1) хост, порт и кодировка тоже нужны для коннекта с СУБД на всякий случай
    //2) вынесем отдельно параметры соединения
    private $cfg = [
        'host' => '127.0.0.1',
        'port' => '3306',
        'dbname' => 'db',
        'charset' => 'utf8',
        'user' => 'dbuser',
        'password' => 'dbpass'
    ];

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
            $params = [
              "mysql:host={$this->cfg['host']}",
              "port={$this->cfg['port']}",
              "dbname={$this->cfg['dbname']}",
              "charset={$this->cfg['charset']}",
            ]
            $dsn = implode(';', $params);
            $user = $this->cfg['user'];
            $password = $this->cfg['password'];
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
        //1) исправляем то же, что и в прошлые коммиты
        //2) SQL вынесем в отдельную переменную для удобства
        $query = "SELECT `id`, `name`, `lastName`, `from`, `age`, `settings` FROM Users WHERE age > :ageFrom LIMIT :limit";
        $stmt = self::getInstance()->prepare($query);
        $stmt->bindValue(':ageFrom', (int) $ageFrom, self::getInstance()::PARAM_INT);
        $stmt->bindValue(':limit', (int) Manager::limit, self::getInstance()::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $users = [];
        foreach ($rows as $row) {
            $settings = json_decode($row['settings'], true);
            $users[] = $settings;
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
        $query = "SELECT `id`, `name`, `lastName`, `from`, `age`, `settings` FROM Users WHERE name = :name";
        $stmt = self::getInstance()->prepare($query);
        //2) вот тут точно не вспомню, но суть в том, что перед отправкой запроса входные значения надо обезвредить:
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
        $query = "INSERT INTO Users (name, lastName, age) VALUES (:name, :lastName, :age)";
        $stmt = self::getInstance()->prepare($query);
        $stmt->bindValue(':name', (string) $name, self::getInstance()::PARAM_STR);
        $stmt->bindValue(':lastName', (string) $lastName, self::getInstance()::PARAM_STR);
        $stmt->bindValue(':age', (int) $age, self::getInstance()::PARAM_INT);
        $stmt->execute();
        return self::getInstance()->lastInsertId();
    }
}