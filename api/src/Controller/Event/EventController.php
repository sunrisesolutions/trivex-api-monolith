<?php

namespace App\Controller\Event;

use App\Entity\Event\Event;
use App\Entity\Event\Registration;
use App\Entity\Organisation\IndividualMember;
use App\Entity\Organisation\Organisation;
use App\Service\SpreadsheetWriter;
use App\Util\StringUtil;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;


class EventController extends AbstractController
{
    /**
     * @Route("/events/{id}/download-attendee-xlsx", name="download_attendee_xlsx")
     */
    public function downloadAttendees(Request $request, $id)
    {
        $eventRepo = $this->getDoctrine()->getRepository(Event::class);
        /** @var Event $event */
        $event = $eventRepo->find($id);
        if (empty($event)) {
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

        $cols = ['id', 'name', 'gender', 'email', 'phoneNumber','memberYNLbl'];
        $columnLetterCode = 65; // Letter A

        $heading = true;
        $regs = $event->getRegistrations();
        /** @var Registration $reg */
        foreach ($regs as $reg) {
            if (empty($attendee = $reg->getAttendee())) {
                continue;
            };
            if ($heading) {
                $heading = false;
                foreach ($cols as $index => $colName) {
                    $columnLetter = chr($columnLetterCode);
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

                $sWriter
                    ->writeCellAndGoRight($attendee->{$getter}());
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
            strtolower('attendee_'.StringUtil::slugify($event->getName())),
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
