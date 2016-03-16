<?php
namespace UserBase\Server\Repository;

use UserBase\Server\Model\AccountAddress;
use RuntimeException;
use PDO;

class PdoAccountAddressRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByAccountName($accountName)
    {
        $sql = 'SELECT * FROM account_address WHERE account_name = :account_name ';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(array(':account_name' => $accountName));
        return $statement->fetchAll();
    }

    public function getById($id)
    {
        $statement = $this->pdo->prepare('SELECT * FROM account_address WHERE id =:id');
        $statement->execute(array(':id' => (int) $id));
        return $statement->fetch();
    }

    public function add(AccountAddress $oModel)
    {
        $sql = 'INSERT INTO account_address
                (account_name, addressline1, addressline2, postalcode, country, city)
                VALUES (:account_name, :addressline1, :addressline2, :postalcode, :country, :city)';

        $statement = $this->pdo->prepare($sql);
        $row = $statement->execute(array(
            ':account_name' => $oModel->getAccountName(),
            ':addressline1' => $oModel->getAddressline1(),
            ':addressline2' => $oModel->getAddressline2(),
            ':postalcode' => $oModel->getPostalcode(),
            ':city' => $oModel->getCity(),
            ':country' => $oModel->getCountry(),
        ));
        return $row;
    }
    public function update(AccountAddress $oModel)
    {
        $statement = $this->pdo->prepare('UPDATE account_address SET
                addressline1 = :addressline1,
                addressline2 =:addressline2,
                postalcode = :postalcode,
                country = :country,
                city = :city
             WHERE id =:id');

        return   $row = $statement->execute(array(
            ':addressline1' => $oModel->getAddressline1(),
            ':addressline2' => $oModel->getAddressline2(),
            ':postalcode' => $oModel->getPostalcode(),
            ':city' => $oModel->getCity(),
            ':country' => $oModel->getCountry(),
            ':id' => $oModel->getId()
        ));
    }

    public function remove($id)
    {
        $statement = $this->pdo->prepare('DELETE FROM account_address  WHERE id =:id');
        return $statement->execute(array(':id' => (int) $id ));
    }
}
