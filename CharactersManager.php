<?php

class CharactersManager 
{
    private $_db;

    public function __construct($db)
    {
        $this->_db = $db;
    }
    
    /**
     * set the value of _db
     * 
     * @return self
     */
    public function setDb(PDO $db)
    {
        $this->_db = $db;
    }

    public function add(Character $char)
    {
        $request = $this->_db->prepare('INSERT INTO characters SET `name` = :name,
        `strength` = :strength, life = :life, `level` = :level, experience = :experience,
        avatar = :avatar;');

        $request->bindValue(':name', $char->getName(), PDO::PARAM_STR);
        $request->bindValue(':strength', $char->getStrength(), PDO::PARAM_INT);
        $request->bindValue(':life', $char->getLife(), PDO::PARAM_INT);
        $request->bindValue(':level', $char->getLevel(), PDO::PARAM_INT);
        $request->bindValue(':experience', $char->getExperience(), PDO::PARAM_INT);
        $request->bindValue(':avatar', $char->getAvatar(), PDO::PARAM_INT);

        $request->execute();

        $lastId = $this->_db->lastInsertId();

        $char->setId($lastId);
    }

    public function delete(Character $char)
    {
        $this->_db->exec('DELETE FROM characters WHERE id = '.$char->getId().';');
    }

    public function getOne($info)
    {
        if (is_int($info)) { // Test si un personnage avec l'id $info existe
            $query = $this->_db->query('SELECT * FROM characters WHERE id = '.$info.';');
            $result = $query->fetch(PDO::FETCH_ASSOC);

            return new Character($result);

        } else { // Sinon, on test avec le nom
            $query = $this->_db->prepare('SELECT * FROM characters WHERE `name` = :name ;');
            $query->execute(array(':name' => $info));

            return new Character($query->fetch(PDO::FETCH_ASSOC));
        }
    }
    
    public function getList()
    {
        $chars = array();

        $query = $this->_db->query('SELECT * FROM characters ORDER BY `name`;');

        while ($line = $query->fetch(PDO::FETCH_ASSOC)) {
            $chars[] = new Character($line);
        }

        return $chars;
    }

    public function getListWithoutOne($id)
    {
        $chars = null;
        
        if (is_int($id)) {
            $query = $this->_db->query('SELECT * FROM characters WHERE id != '.$id.';');

            while ($line = $query->fetch(PDO::FETCH_ASSOC)) {
                $chars[] = new Character($line);
            }

            return $chars;
        }
    }

    public function count()
    {
        return $this->_db->query('SELECT COUNT(*) FROM characters')->fetchColumn();
    }

    public function exists($info)
    {
        if (is_int($info)) { // Test si un personnage avec l'id $info existe
            $query = $this->_db->prepare('SELECT COUNT(*) FROM characters WHERE id = :id');
            $query->execute(array(':id' => $info));

            return (bool) $query->fetchColumn();
        } // Sinon, on test avec le nom

        $query = $this->_db->prepare('SELECT COUNT(*) FROM characters WHERE `name` = :name');
        $query->execute(array(':name' => $info));

        return (bool) $query->fetchColumn();
    }
    
    public function update(Character $char, $action)
    {
        switch ($action) {
            case "Attack": // Mise à jour après une attaque
                $request = $this->_db->prepare('UPDATE characters SET life = :life WHERE id = :id');
        
                $request->bindValue(':id', $char->getId(), PDO::PARAM_INT);
                $request->bindValue(':life', $char->getLife(), PDO::PARAM_INT);
        
                $request->execute();
                break;

            case "Heal": // Mise à jour après un soin
                $request = $this->_db->prepare('UPDATE characters SET life = 100 WHERE id = :id');
        
                $request->bindValue(':id', $char->getId(), PDO::PARAM_INT);
        
                $request->execute();
                break;

            case "Level": // Mise à jour après un niveau gagner
                $request = $this->_db->prepare('UPDATE characters SET `level` = :level, experience = :experience WHERE id = :id');
        
                $request->bindValue(':id', $char->getId(), PDO::PARAM_INT);
                $request->bindValue(':experience', $char->getExperience(), PDO::PARAM_INT);
                $request->bindValue(':level', $char->getLevel(), PDO::PARAM_INT);
        
                $request->execute();
                break;

            case "Experience": // Mise à jour après de l'xp gagner
                $request = $this->_db->prepare('UPDATE characters SET experience = :experience WHERE id = :id');
        
                $request->bindValue(':id', $char->getId(), PDO::PARAM_INT);
                $request->bindValue(':experience', $char->getExperience(), PDO::PARAM_INT);
        
                $request->execute();
                break;
        }
    }
}