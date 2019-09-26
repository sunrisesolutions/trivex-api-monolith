<?php

namespace App\Controller\Messaging;

use App\Entity\Event\Event;
use App\Entity\Event\Registration;
use App\Entity\Messaging\FreeOnMessage;
use App\Entity\Organisation\IndividualMember;
use App\Entity\Organisation\Organisation;
use App\Service\SpreadsheetWriter;
use App\Util\StringUtil;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

class MessageController extends AbstractController
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("/messages/{orgUuid}/download-org-free-on-messages-xlsx", name="download_org_free_on_message_xlsx")
     */
    public function downloadFreeOnMessages(Request $request, $orgUuid)
    {
        $orgRepo = $this->getDoctrine()->getRepository(Organisation::class);
        /** @var Organisation $org */
        $org = $orgRepo->findOneByUuid($orgUuid);

        if (empty($org)) {
            throw new NotFoundHttpException();
        }

        // ask the service for a Excel5
        $phpExcelObject = new Spreadsheet();

        $phpExcelObject->getProperties()->setCreator('Solution')
            ->setLastModifiedBy('Solution')
            ->setTitle('Download - Raw Data')
            ->setSubject('Order Item - Raw Data')
            ->setDescription('Raw Data')
            ->setKeywords('office 2005 openxml php')
            ->setCategory('Raw Data Download');

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $phpExcelObject->setActiveSheetIndex(0);
        $activeSheet = $phpExcelObject->getActiveSheet();
        $sWriter = new SpreadsheetWriter($activeSheet);
        $sWriter->goFirstColumn();
        $sWriter->goFirstRow();

        $cols = ['id', 'senderName', 'subject', 'body', 'freeOnMondays', 'freeOnTuesdays', 'freeOnWednesdays', 'freeOnThursdays', 'freeOnFridays', 'freeOnSaturdays', 'freeOnSundays'];
        $columnLetterCode = 65; // Letter A

        $heading = true;
        $messages = $org->getFreeonMessages();
        /** @var FreeOnMessage $msg */
        foreach ($messages as $msg) {
            if ($heading) {
                $heading = false;
                foreach ($cols as $index => $colName) {
                    $columnLetter = chr($columnLetterCode);
                    $colName = 'xls.label_'.$colName;
                    $colName = $this->translator->trans($colName);
                    $activeSheet
                        ->setCellValue($columnLetter.'1', $colName);
                    $columnLetterCode++;
                }
                $sWriter->goFirstRow();
                $sWriter->goFirstColumn();
            }
            $sWriter->goDown();
            foreach ($cols as $index => $colName) {
                $columnLetter = chr($columnLetterCode);
                $getter = 'get'.ucfirst($colName);
                $activeSheet->getColumnDimension($sWriter->getCursorColumn())
                    ->setAutoSize(true);

                $value = $msg->{$getter}();
                if (is_bool($value)) {
                    $value = $value ? 'YES' : 'NO';
                }
                $sWriter
                    ->writeCellAndGoRight($value);
                $columnLetterCode++;
            }

            $sWriter->goFirstColumn();
        }

//        $repo = $this->getDoctrine()->getRepository(Customer::class);
        // create the writer
        $writer = new Xlsx($phpExcelObject);
        //			$writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');
        // create the response
        //			$response = $this->get('phpexcel')->createStreamedResponse($writer);

        $filename = sprintf(
            'export_%s_%s.%s',
            strtolower('attendee_'.StringUtil::slugify($org->getName())),
            date('Y_m_d_H_i_s', strtotime('now')),
            'xlsx'
        );

        $response = new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            },
            200,
            []
        );

        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');

        $response->headers->set('Content-Disposition', 'attachment;filename='.$filename);

        return $response;
    }
}