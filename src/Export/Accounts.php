<?php
namespace UserBase\Server\Export;

use Symfony\Component\HttpFoundation\Response;
use DataTable\Core\Table;
use DataTable\Core\Writer\Csv as CsvWriter;
use DataTable\Core\Reader\Csv as CsvReader;
use Exception;

class Accounts
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function csvExport()
    {
        $oAccounts = $this->app->getAccountRepository()->getAll();
        $tags = $this->app->getTagRepository()->findAll();
        $properties = $this->app->getPropertyRepository()->findAll();

        $table = new Table();
        $table->setName(basename('accounts.csv'));
        $nameColumn = $table->getColumnByName("name");
        $displayNameColumn = $table->getColumnByName('display_name');
        $aboutColumn = $table->getColumnByName('about');
        $createdAtColumn = $table->getColumnByName('created_at');

        $accountTypeColumn = $table->getColumnByName('account_type');
        $statusColumn = $table->getColumnByName('status');
        $urlColumn = $table->getColumnByName('url');
        $emailColumn = $table->getColumnByName('email');
        $mobileColumn = $table->getColumnByName('mobile');
        $emailVerifiedAt = $table->getColumnByName('email_verified_at');
        $mobileVerfiedAt = $table->getColumnByName('mobile_verfied_at');

        foreach ($tags as $tag) {
            $tagColumn = $table->getColumnByName('tag.' . $tag['name']);
        }
        $accountTags = $this->app->getAccountTagRepository()->findAll();
        foreach ($accountTags as $accountTag) {
            if (isset($oAccounts[$accountTag['account_name']])) {
                $oAccounts[$accountTag['account_name']]->addTagName($accountTag['tag_name']);
            }
        }
        //-- Add properities --//
        foreach ($properties as $property) {
            $propertyColumn = $table->getColumnByName('property.' . $property['name']);
        }
        $accountProperties = $this->app->getAccountPropertyRepository()->findAll();
        foreach ($accountProperties as $accountProperty) {
            if (isset($oAccounts[$accountProperty['account_name']])) {
                $oAccounts[$accountProperty['account_name']]->setPropertyName($accountProperty['name'], $accountProperty['value']);
            }
        }

        $i = 0;
        foreach ($oAccounts as $oAccount) {
            $row = $table->getRowByIndex($i);

            $nameColumn = $row->getCellByColumnName("name");
            $nameColumn->setValue($oAccount->getName());

            $displayNameColumn = $row->getCellByColumnName('display_name');
            $displayNameColumn->setValue($oAccount->getDisplayName());

            $aboutColumn = $row->getCellByColumnName('about');
            $aboutColumn->setValue($oAccount->getAbout());

            $createdAtColumn = $row->getCellByColumnName('created_at');
            $createdAtColumn->setValue((($oAccount->getCreatedAt())?
                date('Y-m-d H:i:s', $oAccount->getCreatedAt()):0));

            $accountTypeColumn = $row->getCellByColumnName('account_type');
            $accountTypeColumn->setValue($oAccount->getAccountType());

            $statusColumn = $row->getCellByColumnName('status');
            $statusColumn->setValue($oAccount->getStatus());

            $urlColumn = $row->getCellByColumnName('url');
            $urlColumn->setValue($oAccount->getUrl());

            $emailColumn = $row->getCellByColumnName('email');
            $emailColumn->setValue($oAccount->getEmail());

            $mobileColumn = $row->getCellByColumnName('mobile');
            $mobileColumn->setValue($oAccount->getMobile());

            $emailVerifiedAt = $row->getCellByColumnName('email_verified_at');
            $emailVerifiedAt->setValue(($oAccount->getEmailVerifiedAt())?
                date('Y-m-d H:i:s', $oAccount->getEmailVerifiedAt()) : 0);

            $mobileVerfiedAt = $row->getCellByColumnName('mobile_verfied_at');
            $mobileVerfiedAt->setValue(($oAccount->getMobileVerifiedAt())?
            date('Y-m-d H:i:s', $oAccount->getMobileVerifiedAt()) : 0);


            foreach ($tags as $tag) {
                $tagColumn = $row->getCellByColumnName('tag.' . $tag['name']);
                if ($oAccount->hasTagName($tag['name'])) {
                    $tagColumn->setValue('Y');
                } else {
                    $tagColumn->setValue('N');
                }
            }

            foreach ($properties as $property) {
                $propertyColumn = $row->getCellByColumnName('property.' . $property['name']);
                if ($oAccount->hasPropertyName($property['name'])) {
                    $propertyColumn->setValue($oAccount->getPropertyNameValue($property['name']));
                } else {
                    $propertyColumn->setValue('');
                }
            }
            $i++;
        }
        // use a writer to export the datatable to a .csv file
        $writer = new CsvWriter();
        $writer->setEnclosure('');
        $output = $writer->write($table);

        $response = new Response($output);
        $response->headers->set('Content-Type', "text/csv");
        $response->headers->set('Content-Disposition', 'attachment; filename="accounts.csv"');
        $response->headers->set('Content-Transfer-Encoding', "binary");
        return $response;
    }
}
