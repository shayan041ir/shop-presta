<?php
namespace PsSqlExcelExport\Controller\Admin;

use PrestaShop\PrestaShop\Core\Domain\SqlManagement\Query\GetSqlRequestExecutionResult;
use PrestaShop\PrestaShop\Core\Domain\SqlManagement\ValueObject\SqlRequestId;
use PsSqlExcelExport\Exporter\SqlRequestExcelExporter;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminPsSqlExcelExportController extends FrameworkBundleAdminController
{
    /** @var object */
    private $queryBus;

    // بدون type-hint برای سازگاری با نسخه‌های مختلف
    public function __construct($queryBus)
    {
        $this->queryBus = $queryBus;
    }

    /**
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))", redirectRoute="admin_sql_requests_index")
     */
    public function export(Request $request, TranslatorInterface $translator)
    {
        $sqlRequestId = (int) $request->get('id_sql_request');
        if ($sqlRequestId <= 0) {
            $this->addFlash('error', $translator->trans('Missing SQL request ID.', [], 'Admin.Notifications.Error'));
            return $this->redirectToRoute('admin_sql_requests_index');
        }

        try {
            $result = $this->queryBus->handle(new GetSqlRequestExecutionResult($sqlRequestId));

            $exporter = new SqlRequestExcelExporter();
            $file = $exporter->exportToFile(new SqlRequestId($sqlRequestId), $result);

            $response = new BinaryFileResponse($file->getPathname());
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file->getFilename());

            register_shutdown_function(static function () use ($file) { @unlink($file->getPathname()); });

            return $response;
        } catch (\Exception $e) {
            $this->addFlash('error', $translator->trans(
                'Error exporting to Excel: %message%',
                ['%message%' => $e->getMessage()],
                'Admin.Notifications.Error'
            ));
            return $this->redirectToRoute('admin_sql_requests_index');
        }
    }
}
