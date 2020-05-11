<?php
    session_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BattlePHP</title>
    <link rel="stylesheet" href="./styles/style.css">
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script type="text/javascript">
    $( document ).ready(function() {
        setTimeout(suiteTraitement, 3000);

        function suiteTraitement() {
            $('.close').trigger('click');
        }
    });
    </script>
</head>
<body>
    <div class="container">

        <!-- NAVBAR -->
        <nav class="navbar navbar-expand-lg bg-classic mb-5">
            <div class="navbar-brand">BattlePHP</div>
            <ul class="navbar-nav mr-auto"></ul>
            <?php

            if (!empty($_POST['action']) && $_POST['action'] == "Déconnexion") { // CONNECTER
                session_unset();
                session_destroy();
            }
            if (!empty($_SESSION['Character']) || $_POST['action'] == "Utiliser" || $_POST['action'] == "Creer") {
                echo "
                <form action='index.php' method='post'>
                    <input class='navbar-brand sign-out-btn' type='submit' value='Déconnexion' name='action' />
                </form>";
            }
            ?>
        </nav>

    <?php

    function loadClass($class) 
        {
            require $class .'.php';
        }

    spl_autoload_register('loadClass');

    loadClass('Character');

    // Data Source Name (DSN)
    $dsn = 'mysql:dbname=battlephp;host=localhost';
    $user = 'root';
    $password = '';

    try {
        $db = new PDO($dsn, $user, $password);

        if ($db) {

            // Alerte a chaque fois qu'une requête échoue
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        
            // Désactive la simulation des requêtes préparés, 
            // utilise l'interface native pour récupérer les données et leur type.
            $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); 

            $manager = new CharactersManager($db);

            if (!empty($_SESSION['Character'])) {
                $myChar = unserialize($_SESSION['Character']); // Notre personnage
            }

            if (!empty($_POST['action'])) { // SI UNE ACTION

                $action = $_POST['action'];

                switch ($action) {
                    case "Attaquer": // Sur attaque
                        $idChar = intval($_POST['id']);
                        $selectedChar = $manager->getOne($idChar);
                        $theAttack = $myChar->attack($selectedChar);

                        switch ($theAttack) { // RESULTAT DE L'ATTAQUE
                            case Character::CHAR_ATTACKED: // PREND DES DEGATS
                                $manager->update($selectedChar, 'Attack');
                                break;

                            case Character::CHAR_KILLED: // EST TUÉ
                                $theKill = $myChar->gainExperience();
                                echo "
                                <div class='alert alert-danger alert-dismissible fade show' role='alert'>
                                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                    <span aria-hidden='true'>&times;</span>
                                    </button>
                                    Vous avez tué <label class='font-weight-bold'>".$selectedChar->getName()."</label>
                                </div>
                                ";

                                switch ($theKill) { // RESULTAT DU KILL
                                    case Character::CHAR_LVL_UP: // ON GAGNE UN NIVEAU
                                        $level = $myChar->getLevel();
                                        $prevLevel = $level-1;
                                        echo "
                                        <div class='alert alert-warning alert-dismissible fade show' role='alert'>
                                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                            <span aria-hidden='true'>&times;</span>
                                            </button>
                                            Vous avez gagner un niveau <label class='font-weight-bold'>(".$prevLevel.">".$level.")</label> 
                                        </div>
                                        ";
                                        $manager->update($myChar, 'Level');
                                        $manager->update($myChar, 'Heal');
                                        break;

                                    case Character::CHAR_EXP_UP: // ON GAGNE DE L'XP
                                        echo "
                                        <div class='alert alert-warning alert-dismissible fade show' role='alert'>
                                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                            <span aria-hidden='true'>&times;</span>
                                            </button>
                                            Vous avez gagner <label class='font-weight-bold'>20</label> experiences
                                        </div>
                                        ";
                                        $manager->update($myChar, 'Experience');
                                        break;

                                    default :
                                    echo "
                                    <div class='text-center error-label py-2 px-5 round'>Erreur dans l'action (Gagner de l'xp ou un niveau)</div>
                                    ";
                                }
                                $_SESSION['Character'] = serialize($myChar);
                                $charName = $selectedChar->getName();
                                $myCharLvl = $myChar->getLevel();

                                $manager->delete($selectedChar);
                                break;

                            case Character::MYSELF: // S'ATTAQUE TOUT SEUL (Erreur)
                                break;

                            default :
                            echo "
                            <div class='text-center error-label py-2 px-5 round'>Erreur dans l'action (Prendre des dégâts ou mourrir)</div>
                            ";
                        }
                        break;

                    case "Soigner": // Sur soins
                        $idChar = intval($_POST['id']);
                        $selectedChar = $manager->getOne($idChar);
                        $charName = $selectedChar->getName();
                        $manager->update($selectedChar, 'Heal');
                        echo "
                        <div class='alert alert-success alert-dismissible fade show' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                            <span aria-hidden='true'>&times;</span>
                            </button>
                            Vous avez soigner <label class='font-weight-bold'>".$charName."</label> 
                        </div>
                        ";
                        break;

                    case "Creer": // Sur création de personnage
                        $newCharName = $_POST['name'];

                        $newChar = new Character(array(
                            'name' => $newCharName,
                            'avatar' => rand(1, 55)
                        ));
                        $manager->add($newChar);
                        $_SESSION['Character'] = serialize($newChar);
                        echo "
                        <div class='alert alert-success alert-dismissible fade show' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                            <span aria-hidden='true'>&times;</span>
                            </button>
                            Création de <label class='font-weight-bold'>".$newCharName."</label>
                        </div>
                        ";
                        break;

                    case "Utiliser": // Sur utilisation de personnage
                        $charId = intval($_POST['id']);
                        $char = $manager->getOne($charId);
                        $charName = $char->getName();
                        $_SESSION['Character'] = serialize($char);
                        echo "
                        <div class='alert alert-success alert-dismissible fade show' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                            <span aria-hidden='true'>&times;</span>
                            </button>
                            Connexion avec <label class='font-weight-bold'>".$charName."</label>
                        </div>
                        ";
                        break;

                    case "Déconnexion": // Sur déconnexion
                        echo "
                        <div class='alert alert-success alert-dismissible fade show' role='alert'>
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                            <span aria-hidden='true'>&times;</span>
                            </button>
                            Déconnecté
                        </div>
                        ";
                        break;

                    default :
                        echo "
                        <div class='text-center error-label py-2 px-5 round'>Erreur une action</div>
                        ";
                }
            }   


            if (!empty($_SESSION['Character'])) {

                if (!empty($_SESSION['Character'])) {
                    $myChar = unserialize($_SESSION['Character']); // Notre personnage
                }
            
                // AFFICHAGE DES INFORMATION QUAND ON EST CONNECTÉ

                $currentCharId = $myChar->getId();
                $currentCharName = $myChar->getName();
                $currentCharLife = $myChar->getLife();
                $currentCharStrength = $myChar->getStrength();
                $currentCharLevel = $myChar->getLevel();
                $currentCharExperience = $myChar->getExperience();
                $currentCharAvatar = $myChar->getAvatar();


                // PANNEAU DE NOTRE PERSONNAGE
                echo "
                <div class='bg-own p-3 my-3 own-pannel round'>
                    <div class='row char-row'>
                        <div class='col-md-auto'>
                            <div class='avatar-box' style='background-image: url("."./images/avatars/avatar-".$currentCharAvatar.".png".")'></div>
                        </div>
                        <div class='col d-flex'>
                            <div class='d-flex flex-column'>
                                <div class='char-name'>".$currentCharName."</div>
                                <div class='char-infos'>
                                    <div class='char-infos-box'><label>".$currentCharLife."</label></div>
                                    <div class='char-infos-box'><label>".$currentCharStrength."</label></div>
                                    <div class='char-infos-box'><label>".$currentCharLevel."</label></div>
                                    <div class='char-infos-box'><label>".$currentCharExperience."</label></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                ";


                // PANNEAU ADVERSAIRES
                $charList = $manager->getListWithoutOne($currentCharId);

                if ($charList) {

                    echo "<div class='px-3 char-pannel'>";
                    foreach ($charList as $char) {

                        $charId = $char->getId();
                        $charName = $char->getName();
                        $charLife = $char->getLife();
                        $charStrength = $char->getStrength();
                        $charLevel = $char->getLevel();
                        $charExperience = $char->getExperience();
                        $charAvatar = $char->getAvatar();


                        echo "
                        <div class='row char-row py-3 bg-classic'>
                            <div class='col-md-auto'>
                                <div class='avatar-box' style='background-image: url("."./images/avatars/avatar-".$charAvatar.".png".")'></div>
                            </div>
                            <div class='col d-flex'>
                                <div class='d-flex flex-column'>
                                    <div class='char-name'>".$charName."</div>
                                    <div class='char-infos'>
                                        <div class='char-infos-box'><label>".$charLife."</label></div>
                                        <div class='char-infos-box'><label>".$charStrength."</label></div>
                                        <div class='char-infos-box'><label>".$charLevel."</label></div>
                                        <div class='char-infos-box'><label>".$charExperience."</label></div>
                                    </div>
                                </div>
                            </div>
                            <form action='index.php' method='post'>
                                <div class='col-md-auto d-flex justify-content-center'>
                                    <input type='hidden' value='".$charId."' name='id'>
                                    <input type='submit' value='Attaquer' class='attack-btn round' name='action'/>
                                </div>
                            </form>
                            <form action='index.php' method='post'>
                                <div class='col-md-auto d-flex justify-content-center'>
                                    <input type='hidden' value='".$charId."' name='id'>
                                    <input type='submit' value='Soigner' class='use-btn round' name='action'/>
                                </div>
                            </form>
                        </div>
                        ";
                    }
                    echo "</div>";

                } else { // Si il n'y a pas encore d'adversaires
                    echo "
                    <div class='text-center error-label py-2 px-5 round'>Aucun adversaire</div>
                    ";
                }

            } else {
                // AFFICHAGE DES INFORMATION QUAND ON EST DECONNECTÉ
        
                // PANNEAU CREATION PERSONNAGE
                echo "
                <div class='bg-own p-3 my-3 own-pannel round'>
                    <form action='index.php' method='post'>
                        <div class='row'>
                            <div class='col d-flex justify-content-center'>
                                <input type='text' placeholder='Nom du personnage à créer' class='create-input' name='name' maxlength='30' required/>
                            </div>
                            <div class='col-md-auto d-flex justify-content-center create-btn-container'>
                                <button type='submit' value='Creer' class='create-btn' name='action'>+</button>
                            </div>
                        </div>
                    </form>
                </div>
                ";

                // PANNEAU DES PERSONNAGES EXISTANTS
                $charList = $manager->getList();

                if ($charList) {

                    echo "<div class='px-3 char-pannel'>";
                    foreach ($charList as $char) {

                        $charId = $char->getId();
                        $charName = $char->getName();
                        $charLife = $char->getLife();
                        $charStrength = $char->getStrength();
                        $charLevel = $char->getLevel();
                        $charExperience = $char->getExperience();
                        $charAvatar = $char->getAvatar();

                        echo "
                        <div class='row char-row py-3 bg-classic'>
                            <div class='col-md-auto'>
                                <div class='avatar-box' style='background-image: url("."./images/avatars/avatar-".$charAvatar.".png".")'></div>
                            </div>
                            <div class='col d-flex'>
                                <div class='d-flex flex-column'>
                                    <div class='char-name'>".$charName."</div>
                                    <div class='char-infos'>
                                        <div class='char-infos-box'><label>".$charLife."</label></div>
                                        <div class='char-infos-box'><label>".$charStrength."</label></div>
                                        <div class='char-infos-box'><label>".$charLevel."</label></div>
                                        <div class='char-infos-box'><label>".$charExperience."</label></div>
                                    </div>
                                </div>
                            </div>
                            <form action='index.php' method='post'>
                                <div class='col-md-auto d-flex justify-content-center'>
                                    <input type='hidden' value='".$charId."' name='id'>
                                    <input type='submit' value='Utiliser' class='use-btn round' name='action'/>
                                </div>
                            </form>
                        </div>
                        ";
                    }
                    echo "</div>";

                } else { // Si il n'y a pas encore d'adversaires
                    echo "
                    <div class='text-center error-label py-2 px-5 round'>Aucun adversaire</div>
                    ";
                }

            }
        }
    } catch (PDOException $e) {
        echo "
        <div class='text-center error-label py-2 px-5 round'>Erreur de connexion : ".$e->getMessage()."</div>
        ";
    }
    ?>

    </div>
</body>
</html>