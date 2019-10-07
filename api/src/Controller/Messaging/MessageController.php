<?php

namespace App\Controller\Messaging;

use App\Entity\Event\Event;
use App\Entity\Event\Registration;
use App\Entity\Messaging\Delivery;
use App\Entity\Messaging\FreeOnMessage;
use App\Entity\Messaging\Message;
use App\Entity\Messaging\MessageOption;
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
     * @Route("/messages/{messageId}/my-selected-options", name="selected_options")
     */
    public function mySelectedOptions(Request $request, $messageId)
    {
        $registry = $this->getDoctrine();
        $message = $registry->getRepository(Message::class)->find($messageId);
        if (empty($message)) {
            throw new NotFoundHttpException();
        }
        $r = [];

        return new JsonResponse($r);
    }

    /**
     * @Route("/messages/{messageId}/delivery-stats", name="delivery_stats")
     */
    public function deliveryStats(Request $request, $messageId)
    {
        $registry = $this->getDoctrine();
        $message = $registry->getRepository(Message::class)->find($messageId);
        if (empty($message)) {
            throw new NotFoundHttpException();
        }

        $selectedOption = $request->get('selectedOptions');

        $qb = $registry->getRepository(Delivery::class)->createQueryBuilder('delivery');
        $expr = $qb->expr();
        $qb->andWhere($expr->like('delivery.selectedOptions', $expr->literal('%'.$selectedOption.'%')));
        $qb->andWhere($expr->eq('delivery.message', $message->getId()));

        $totalItems = count($qb->getQuery()->getResult());

        return new JsonResponse(['hydra:totalItems' => $totalItems
        ]);
    }

    /**
     * @Route("/messages/{messageId}/download-org-simple-message-deliveries-xlsx", name="download_org_simple_message_deliveries_xlsx")
     */
    public function downloadSimpleMessageDeliveries(Request $request, $messageId)
    {
        $registry = $this->getDoctrine();
        $message = $registry->getRepository(Message::class)->find($messageId);
        if (empty($message)) {
            throw new NotFoundHttpException();
        }

        $optionRepo = $registry->getRepository(MessageOption::class);
        $selectedOption = $request->get('selectedOptions');

        $qb = $registry->getRepository(Delivery::class)->createQueryBuilder('delivery');
        $expr = $qb->expr();
//        $qb->andWhere($expr->like('delivery.selectedOptions', $expr->literal('%'.$selectedOption.'%')));
        $qb->andWhere($expr->eq('delivery.message', $message->getId()));

        $deliveries = $qb->getQuery()->getResult();

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

        $cols = ['id', 'senderName', 'recipientName', 'subject'];
        if (!empty($message->getOptionSet())) {
            $cols[] = 'selectedOptions';
        } else {

        }

        $columnLetterCode = 65; // Letter A

        $selectedOptions = [];

        $heading = true;
        /** @var Delivery $delivery */
        foreach ($deliveries as $delivery) {
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

                $value = $delivery->{$getter}();
                if (is_bool($value)) {
                    $value = $value ? 'YES' : 'NO';
                }
                if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d');
                }
                if ($colName === 'selectedOptions') {
                    $selectedOptionUuids = $value;
                    foreach ($selectedOptionUuids as $optionUuid) {
                        if (array_key_exists($optionUuid, $selectedOptions)) {
                            /** @var MessageOption $selectedOption */
                            $selectedOption = $selectedOptions[$optionUuid];
                        } else {
                            /** @var MessageOption $selectedOption */
                            $selectedOption = $selectedOptions[$optionUuid] = $optionRepo->findOneByUuid($optionUuid);
                        }
                        $sWriter
                            ->writeCellAndGoRight($selectedOption->getName());
                    }
                } else {
                    $sWriter
                        ->writeCellAndGoRight($value);
                }
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
            strtolower('messages_'.StringUtil::slugify($message->getSubject())),
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

        $cols = ['id', 'senderName', 'subject', 'body', 'freeOnMondays', 'freeOnTuesdays', 'freeOnWednesdays', 'freeOnThursdays', 'freeOnFridays', 'freeOnSaturdays', 'freeOnSundays', 'effectiveFrom', 'expireAt', 'senderGroupName'];
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
                if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d');
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
            strtolower('free_on_messages_'.StringUtil::slugify($org->getName())),
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
