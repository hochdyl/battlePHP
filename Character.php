<?php

class Character 
{
    private $_id;
    private $_name;
    private $_strength = 8;
    private $_life = 100;
    private $_level = 1;
    private $_experience = 0;
    private $_avatar;

    private static $_counter = 0;

    const FULL_LIFE = 100;
    const BASE_LVL = 1;
    const BASE_EXPERIENCE = 1;
    const CHAR_ATTACKED = 1;
    const CHAR_KILLED = 2;
    const MYSELF = 3;
    const CHAR_LVL_UP = 4;
    const CHAR_EXP_UP = 5;

    public function __construct(array $line)
    {
        $this->hydrate($line);
    }

    public function hydrate(array $line)
    {
        foreach ($line as $key => $value) {
            $method = 'set'.ucfirst($key);
            if (method_exists($this,$method)) {
                $this->$method($value);
            }
        }
    }

    public function getId()
    {
        return $this->_id;
    }

    public function setId($id)
    {
        $id = (int) $id;
        
        if ($id > 0) {
            $this->_id = $id;
        }
    }

    public function getName()
    {
        return $this->_name;
    }

    public function setName($name)
    {
        if (!is_string($name)) {
            trigger_error("Le nom du personnage est inccorect", E_USER_WARNING);
            return;
        }
        
        $this->_name = $name;
        return $this;
    }

    public function getLife()
    {
        return $this->_life;
    }

    public function setLife($life)
    {
        if ($life <= 0) {
            trigger_error("La vie du personnage ne doit pas être inférieur à 0", E_USER_WARNING);
            return;
        }
        
        $this->_life = $life;
        return $this;
    }

    public function getStrength()
    {
        return $this->_strength;
    }

    public function setStrength($strength)
    {
        if ($strength < 0) {
            trigger_error("La force du personnage ne doit pas être inférieur à 0", E_USER_WARNING);
        }

        $this->_strength = $strength;
        return $this;
    }
    
    public function getExperience()
    {
        return $this->_experience;
    }

    public function setExperience($experience)
    {
        if ($experience < 0 || $experience > 100) {
            trigger_error("L'experience du personnage doit être comprise entre 0 et 100", E_USER_WARNING);
        }
        
        $this->_experience = $experience;
        return $this;
    }

    public function getLevel() 
    {
        return $this->_level;
    }

    public function setLevel($level)
    {
        $level = (int) $level;
        
        if ($level <= 0) {
            trigger_error("Le niveau du personnage doit être supérieur à 0", E_USER_WARNING);
        }

        $this->_level = $level;
        return $this;
    }

    public function getAvatar()
    {
        return $this->_avatar;
    }

    public function setAvatar($avatar)
    {
        $avatar = (int) $avatar;
        
        if ($avatar >= 0) {
            $this->_avatar = $avatar;
        }
    }

    public function attack(Character $char)
    {
        if ($char->getId() == $this->_id) {
            return self::MYSELF;
        }
        $strength = $this->_strength;

        return $char->takeAttack($strength);
    }
    
    public function takeAttack($strength)
    {
        $this->_life -= $strength;
        if ($this->_life <= 0) {
            return self::CHAR_KILLED;
        }

        return self::CHAR_ATTACKED;
    }

    public function gainExperience()
    {
        $newExperience = $this->_experience += 20/$this->_level;

        return $this->gainLevel($newExperience);
    }

    public function gainLevel($experience)
    {
        if ($experience >= 100) {
            
            $this->_level += 1;
            $this->_experience = 0;

            return $this->gainStrength();
        }
        return self::CHAR_EXP_UP;
    }
    
    public function gainStrength()
    {
        $this->_strength += 2;

        return self::CHAR_LVL_UP;
    }
}