# README #

### Class SQL ###

Création d'une nouvelle classe pointant sur une table SQL.
Il faut obligatoirement ajouter l'héritage**extends Astaroth\DbClass**, et indiquer le nom de la table avec**setTableName(\<Nom de la table>)**

Ensuite ajoutés les attibuts sql avec**$this->add(\<Nom de l'attribut>, \<Type de l'attribut>, \<La valeur par défault> = null)**

Si vous souhaitez ajouté des attributs qui ne sont pas en SQL utiliser**$this->addNoSQL(\<Nom de l'attribut>, \<Type de l'attribut>, \<La valeur par défault> = null)**

#### Example n°1 ####

    class SQL_Account extends Astaroth\DbClass {
        protected function base() {
            $this->setTableName('account');
            $this->add('id', 'int', -1);
            $this->add('nom', 'string', null);
            $this->addNoSQL('nomdetest', 'string', null);
        }
    }
Vous devez indiquer les clé primaire (pour optimiser les requêtes, ...)

#### Example n°2 ####

    class SQL_Account extends Astaroth\DbClass {
        protected function base() {
            $this->setTableName('account');
            $this->add('id', 'int', -1);
            $this->add('id2', 'int', -1);
            $this->setPrimaryKey('id', 'id2');
            Ou
            $this->setPrimaryKey('id');
            $this->setPrimaryKey('id2');
        }
    }

### Utilisation d'un objet SQL ###

#### Méthode de Base ####

**Save(\<colonnes="\*">, \<>)**

**Get()**

#### Personifisation ####

**setWhere->add(\<Oprétareur>, \<Attribut>, \<Valeur>, \<AND/OR>)**
#### Example ####
    $account = new SQL_Account();
    $account->setWhere()->add(\Astaroth\DbWhereOperator::EQUAL, 'id', 1);
    $account->Get();

**setAfterWhere->add(\<Oprétareur>, \<Attribut>, \<Valeur>)**
#### Example ####

    $account = new SQL_Account();
    $account->setAfterWhere()->add(\Astaroth\DbAfterWhereOperator::LIMIT, null, false, 1);
    $account->Get();

### Plus en profondeur ###

