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

        $table = new Table();
        $table->setName(basename('accounts.csv'));
        $nameColumn = $table->getColumnByName("name");
        $displayNameColumn = $table->getColumnByName('display_name');
        $aboutColumn = $table->getColumnByName('about');
        $createdAtColumn = $table->getColumnByName('created_at');
        $deletedAtColumn = $table->getColumnByName('deleted_at');
        $accountTypeColumn = $table->getColumnByName('account_type');
        $statusColumn = $table->getColumnByName('status');
        $urlColumn = $table->getColumnByName('url');
        $emailColumn = $table->getColumnByName('email');
        $mobileColumn = $table->getColumnByName('mobile');
        $emailVerifiedAt = $table->getColumnByName('email_verified_at');
        $mobileVerfiedAt = $table->getColumnByName('mobile_verfied_at');


        for ($i = 0; $i < count($oAccounts); $i++) {
            $row = $table->getRowByIndex($i);

            $nameColumn = $row->getCellByColumnName("name");
            $nameColumn->setValue($oAccounts[$i]->getName());

            $displayNameColumn = $row->getCellByColumnName('display_name');
            $displayNameColumn->setValue($oAccounts[$i]->getDisplayName());

            $aboutColumn = $row->getCellByColumnName('about');
            $aboutColumn->setValue($oAccounts[$i]->getAbout());

            $createdAtColumn = $row->getCellByColumnName('created_at');
            $createdAtColumn->setValue((($oAccounts[$i]->getCreatedAt())?
                date('Y-m-d H:i:s', $oAccounts[$i]->getCreatedAt()):0));

            $deletedAtColumn = $row->getCellByColumnName('deleted_at');
            $deletedAtColumn->setValue((($oAccounts[$i]->getDeletedAt())?
                date('Y-m-d H:i:s', $oAccounts[$i]->getDeletedAt()):0));

            $accountTypeColumn = $row->getCellByColumnName('account_type');
            $accountTypeColumn->setValue($oAccounts[$i]->getAccountType());

            $statusColumn = $row->getCellByColumnName('status');
            $statusColumn->setValue($oAccounts[$i]->getStatus());

            $urlColumn = $row->getCellByColumnName('url');
            $urlColumn->setValue($oAccounts[$i]->getUrl());

            $emailColumn = $row->getCellByColumnName('email');
            $emailColumn->setValue($oAccounts[$i]->getEmail());

            $mobileColumn = $row->getCellByColumnName('mobile');
            $mobileColumn->setValue($oAccounts[$i]->getMobile());

            $emailVerifiedAt = $row->getCellByColumnName('email_verified_at');
            $emailVerifiedAt->setValue($oAccounts[$i]->getEmailVerifiedAt());

            $mobileVerfiedAt = $row->getCellByColumnName('mobile_verfied_at');
            $mobileVerfiedAt->setValue($oAccounts[$i]->getMobileVerifiedAt());
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
