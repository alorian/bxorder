<?

use Bitrix\Main\Localization\Loc;

IncludeModuleLangFile(__FILE__);

if (class_exists('opensource_order')) {
    return;
}

Class opensource_order extends CModule
{
    const MODULE_ID = 'opensource.order';
    public $MODULE_ID = 'opensource.order';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_CSS;

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('opensource_order_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('opensource_order_MODULE_DESC');

        $this->PARTNER_NAME = Loc::getMessage('opensource_order_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('opensource_order_PARTNER_URI');
    }

    /**
     * Get /document/local (when exists) or /document/bitrix.
     * @return string
     */
    private function getRoot()
    {
        $local = $_SERVER['DOCUMENT_ROOT'] . '/local';
        return is_dir($local) ? $local : $_SERVER['DOCUMENT_ROOT'] . BX_ROOT;
    }

    function InstallFiles()
    {
        CopyDirFiles(__DIR__ . '/components', $this->getRoot() . "/components", true, true);
    }

    function UnInstallFiles()
    {
        DeleteDirFilesEx(BX_ROOT . '/components/opensource/order');
        DeleteDirFilesEx('/local/components/opensource/order');
    }

    function DoInstall()
    {
        RegisterModule(self::MODULE_ID);
        $this->InstallFiles();
    }

    function DoUninstall()
    {
        UnRegisterModule(self::MODULE_ID);
        $this->UnInstallFiles();
    }
}