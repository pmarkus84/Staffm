<?php

/*
 * Copyright (C) 2018 pm-webdesign.eu 
 * Markus Puffer <m.puffer@pm-webdesign.eu>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

namespace Pmwebdesign\Staffm\Controller; // Class must exist in composer.json under ps4

use PHPOffice\PhpSpreadsheet\Spreadsheet;
use PHPOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPOffice\PhpSpreadsheet\Writer\Xlsx;
use Pmwebdesign\Staffm\Domain\Model\Firma;
use Pmwebdesign\Staffm\Domain\Model\Kostenstelle;
use Pmwebdesign\Staffm\Domain\Model\Mitarbeiter;
use Pmwebdesign\Staffm\Domain\Model\Position;
use Pmwebdesign\Staffm\Domain\Model\Qualifikation;
use Pmwebdesign\Staffm\Domain\Repository\MitarbeiterRepository;
use Pmwebdesign\Staffm\Domain\Repository\QualifikationRepository;
use Pmwebdesign\Staffm\Domain\Service\QualificationService;
use Pmwebdesign\Staffm\Domain\Service\UserService;
use Pmwebdesign\Staffm\Property\TypeConverter\UploadedFileReferenceConverter;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Pmwebdesign\Staffm\Utility\ClassUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;

/**
 * Employee Controller
 * 
 * @author Markus Puffer (m.puffer@pm-webdesign.eu)
 */
class MitarbeiterController extends ActionController
{

    /**
     * Caching Framework
     *
     * @var CacheManager     
     */
    protected $cache;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Pmwebdesign\Staffm\Domain\Model\Mitarbeiter>
     */
    protected $objects;

    /**
     * Employee Repository
     * 
     * @var MitarbeiterRepository     
     */
    protected $mitarbeiterRepository = NULL;

    /**
     * Qualification Repository
     * 
     * @var \Pmwebdesign\Staffm\Domain\Repository\qualifikationRepository     
     */
    protected $qualifikationRepository = NULL;

    /** Persistence Manager
     * Manages objects from the repositories
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     * @inject
     */
    protected $persistenceManager;

    /**
     * Inject employee repository
     * 
     * @param MitarbeiterRepository $mitarbeiterRepository
     */
    public function injectMitarbeiterRepository(MitarbeiterRepository $mitarbeiterRepository)
    {
        $this->mitarbeiterRepository = $mitarbeiterRepository;
    }

    /**
     * Inject qualification repository
     * 
     * @param \Pmwebdesign\Staffm\Domain\Repository\MitarbeiterqualifikationRepository $mitarbeiterqualifikationRepository
     */
    public function injectQualifikationRepository(QualifikationRepository $qualifikationRepository)
    {
        $this->qualifikationRepository = $qualifikationRepository;
    }

    /**
     * Initialize View
     * 
     * @param ViewInterface $view
     * @return void
     */
    public function initializeView(ViewInterface $view)
    {
        $pluginName = $this->request->getPluginName(); // Get Plugin name
        // Plugin = Supervisor?
        if ($pluginName == "Staffmvorg") {
            $this->view->setLayoutPathAndFilename('typo3conf/ext/staffm/Resources/Private/Layouts/LoginLayout.html');
        } elseif ($pluginName == "Staffmcustom") {
            $this->view->setLayoutPathAndFilename('typo3conf/ext/staffm/Resources/Private/Layouts/Staffmcustom.html');
        }

        $this->view->assign('menuname', 'Mitarbeiter');   
    }

    /**
     * Export employe data to Excel          
     * 
     * @return void
     */
    public function exportAction()
    {
        if ($this->request->hasArgument('searching')) {
            $search = $this->request->getArgument('searching');
        }

        $aktpfad = $_SERVER['DOCUMENT_ROOT'];
        $filePath = $aktpfad . "/uploads/tx_staffm/export.xlsx";

        $limit = 0;
        $mitarbeiters = $this->mitarbeiterRepository->findSearchForm($search, $limit);

        $_oPHPExcel = new Spreadsheet();
        $_oExcelWriter = new Xlsx($_oPHPExcel);

        // Create new Worksheet
        $myWorkSheet = new Worksheet($_oPHPExcel, 'Mitarbeiter');
        $_oPHPExcel->addSheet($myWorkSheet, 0);
        $sheetIndex = $_oPHPExcel->getIndex(
                $_oPHPExcel->getSheetByName('Worksheet')
        );
        // Delete standard worksheet
        $_oPHPExcel->removeSheetByIndex($sheetIndex);

        $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1, 1, 'Nachname');
        $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(2, 1, 'Vorname');
        $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(3, 1, 'PersNr');
        $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(4, 1, 'AD-Name');
        $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(5, 1, 'Titel');
        $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(6, 1, 'Telefon');
        $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(7, 1, 'Handy');
        $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(8, 1, 'Fax');
        $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(9, 1, 'Email');
        $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(10, 1, 'Kostenstelle');
        $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(11, 1, 'KST_Bezeichnung');
        $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(12, 1, 'Firma');        
        for ($i = 0; $i < count($mitarbeiters); $i++) {
            $mitarbeiter = new Mitarbeiter();
            $mitarbeiter = $mitarbeiters[$i];
            $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1, $i + 2, $mitarbeiter->getLastName());
            $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(2, $i + 2, $mitarbeiter->getFirstName());
            $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(3, $i + 2, (string) $mitarbeiter->getPersonalnummer());
            $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(4, $i + 2, $mitarbeiter->getUsername());
            $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(5, $i + 2, $mitarbeiter->getTitle());
            $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(6, $i + 2, $mitarbeiter->getTelephone());
            $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(7, $i + 2, $mitarbeiter->getHandy());
            $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(8, $i + 2, $mitarbeiter->getFax());
            $_oPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(9, $i + 2, $mitarbeiter->getEmail());            
            if ($mitarbeiter->getKostenstelle() != NULL) {
                $_oPHPExcel->getSheetByName("Mitarbeiter")->setCellValueByColumnAndRow(10, $i + 2, $mitarbeiter->getKostenstelle()->getNummer());  
                $_oPHPExcel->getSheetByName("Mitarbeiter")->setCellValueByColumnAndRow(11, $i + 2, $mitarbeiter->getKostenstelle()->getBezeichnung());                
            }
            if ($mitarbeiter->getFirma() != null) {
                $_oPHPExcel->getSheetByName("Mitarbeiter")->setCellValueByColumnAndRow(12, $i + 2, $mitarbeiter->getFirma()->getBezeichnung());
            }
        }

        // Save Excel file on intranet server
        $_oExcelWriter->save($filePath);
        unset($_oExcelWriter);
        unset($_oPHPExcel);

        // "Save as" as Download for users
        $size = filesize($filePath);        
        header("Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-disposition: attachment; filename=\"export.xlsx\"");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: $size");        
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        ob_clean(); // Very important otherwise there is a mistake at Excel file
        flush(); // Very important otherwise there is a mistake at Excel file
        readfile($filePath);

        // Delete Excel file at the server
        unlink($filePath);
    }

    /**
     * List of employees
     * 
     * @param \Pmwebdesign\Staffm\Domain\Model\Category $category
     * @param Kostenstelle $kostenstelle
     * @param \Pmwebdesign\Staffm\Domain\Model\Mitarbeiter $mitarbeiter
     * @return void
     */
    public function listAction(\Pmwebdesign\Staffm\Domain\Model\Category $category = NULL, Kostenstelle $kostenstelle = NULL,
            \Pmwebdesign\Staffm\Domain\Model\Mitarbeiter $mitarbeiter = NULL)
    {        
        // Search exist?
        if ($this->request->hasArgument('search')) {
            $search = $this->request->getArgument('search');
        }

        // Clicked char?
        $char = "";
        if ($this->request->hasArgument('@widget_0')) {
            $widget = $this->request->getArgument('@widget_0');
            $char = $widget["char"];
            $search = "";
        }

        // Memorized id?
        $maid = "";
        if ($this->request->hasArgument('maid')) {
            $maid = $this->request->getArgument('maid');
        }

        // No caching flag?
        if ($this->request->hasArgument('cache')) {
            $cache = $this->request->getArgument('cache');
        }
        
        if ($this->request->hasArgument('art')) {
            $art = $this->request->getArgument('art');
            $this->view->assign('art', $art);
        }    
                
        /** @var CacheService $cacheService */
        $cacheService = GeneralUtility::makeInstance(\Pmwebdesign\Staffm\Domain\Service\CacheService::class);    
                                        
        // No cache flag? (Example employee was deleted and the info message is shown in the list view)       
        if ($cache != "notcache" && $search == "") {
            // Cache exist?       
            if (($output = $cacheService->getCache($this->request->getControllerActionName(), $this->request->getControllerName(), $char, $maid, 0)) != NULL) {
                // Show Cache-Page
                return $output;
            }
        }

        $limit = 0;
        $mitarbeiters = $this->mitarbeiterRepository->findSearchForm($search, $limit);
       
        if ($this->request->hasArgument('key')) {
            $key = $this->request->getArgument('key');
            $this->view->assign('key', $key);
        }
        if ($this->request->hasArgument('userKey')) {
            $userKey = $this->request->getArgument('userKey');
            $this->view->assign('userKey', $userKey);
        }        
        $this->view->assign('mitarbeiters', $mitarbeiters);
        $this->view->assign('kostenstelle', $kostenstelle);
        $this->view->assign('category', $category);
        $this->view->assign('mitarbeiter', $mitarbeiter);
        if ($maid != "") {
            $this->view->assign('maid', $maid);
        }

        // Search exist?
        if ($search <> "" || $cache == "notcache") {
            // Yes, no Cache is needed
            $this->view->assign('cache', '');
            $this->view->assign('search', $search);
        } else {
            // No, set Cache
            $output = $this->view->render();
            $cacheService->setCache($this->request->getControllerActionName(), $this->request->getControllerName(), $output, $char, $maid, 0);
            return $output;
        }
    }

    /**
     * List of employees for supervisors and deputies
     * 
     * @param Kostenstelle $kostenstelle
     * @return void
     */
    public function listVgsAction(Kostenstelle $kostenstelle = NULL)
    {
        if ($this->request->hasArgument('search')) {
            $search = $this->request->getArgument('search');
            $this->view->assign('search', $search);
        }
        if ($this->request->hasArgument('key')) {
            $key = $this->request->getArgument('key');
            $this->view->assign('key', $key);
        }

        // Logged in user              
        $aktuser = $this->objectManager->get('Pmwebdesign\\Staffm\\Domain\\Repository\\MitarbeiterRepository')->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        if ($aktuser != NULL) {
            $this->view->assign('aktuser', $aktuser);
        }

        $limit = 0;
        if ($this->request->hasArgument('maid')) {
            $maid = $this->request->getArgument('maid');
            $this->view->assign('maid', $maid);
        }
        $mitarbeiters = $this->mitarbeiterRepository->findMitarbeiterVonVorgesetzten($search, $aktuser);
        $this->view->assign('mitarbeiters', $mitarbeiters);
        $this->view->assign('kostenstelle', $kostenstelle);
    }

    /**
     * List of employees who choosed in Backend
     *     
     * @return void
     */
    public function listCustomAction()
    {
        $limit = 0;

        $userService = GeneralUtility::makeInstance(UserService::class);
        $mitarbeiters = $userService->getSettingUsers($this->settings["choosedusers"]);
        $templateart = $this->settings['chooseview'];
        $this->view->assign('mitarbeiters', $mitarbeiters);
        $this->view->assign('templateart', $templateart);
    }

    /**
     * List of employees for choosing a responible for a cost center
     * 
     * @param Kostenstelle $kostenstelle
     * @return void
     */
    public function listChooseAction(Kostenstelle $kostenstelle)
    {
        // Search exist?
        if ($this->request->hasArgument('search')) {
            $search = $this->request->getArgument('search');    
            $this->view->assign('search', $search);
        }        
        $mitarbeiters = $this->mitarbeiterRepository->findSearchForm($search, 0);        
        $this->view->assign('mitarbeiters', $mitarbeiters);
        $this->view->assign('kostenstelle', $kostenstelle);
    }

    /**
     * Choosing employees for a qualification
     * 
     * @param Qualifikation $qualifikation
     */
    public function listChooseQualiAction(Qualifikation $qualifikation)
    {
        $mitarbeiters = $this->mitarbeiterRepository->findSearchForm('', 0);
        $this->view->assign('mitarbeiters', $mitarbeiters);
        $this->view->assign('qualifikation', $qualifikation);
    }

    /**
     * Detail view of cost center responsible
     * 	
     * @param Kostenstelle $kostenstelle
     * @return void
     */
    public function showVeraKstAction(Kostenstelle $kostenstelle)
    {
        $mitarbeiter = $kostenstelle->getVerantwortlicher();

        // Search word?
        if ($this->request->hasArgument('search')) {
            $search = $this->request->getArgument('search');
            $this->view->assign('search', $search);
        }
        $this->view->assign('mitarbeiter', $mitarbeiter);
        $this->view->assign('kostenstelle', $kostenstelle);
    }

    /**
     * Detail view of employee from other show Actions
     * Example (Cost Center, Qualification, aso.)
     * 
     * @param int $ma
     * @param Position $position
     * @param Kostenstelle $kostenstelle
     * @param Firma $firma
     * @param Qualifikation $qualifikation
     * @return void
     */
    public function showKstAction($ma = NULL, Position $position = NULL, Kostenstelle $kostenstelle = NULL, Firma $firma = NULL, Qualifikation $qualifikation = NULL)
    {
        if ($ma == NULL) {
            $ma = new Mitarbeiter();
            $ma = $kostenstelle->getVerantwortlicher();
        } else {
            $ma = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\MitarbeiterRepository::class)->findByUid($ma);
        }

        // Search word?
        if ($this->request->hasArgument('search')) {
            $search = $this->request->getArgument('search');
            $this->view->assign('search', $search);
        }

        $this->view->assign('mitarbeiter', $ma);
        $this->view->assign('position', $position);
        $this->view->assign('kostenstelle', $kostenstelle);
        $this->view->assign('firma', $firma);
        $this->view->assign('qualifikation', $qualifikation);
    }

    /**
     * Show the details from an employee
     * 
     * @param int $mitarbeiter	
     * @return void
     */
    public function showAction($mitarbeiter)
    {
        $mitarbeiter = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\MitarbeiterRepository::class)->findByUid($mitarbeiter);
        
        if ($this->request->hasArgument('key')) {
            $key = $this->request->getArgument('key');
            $this->view->assign('key', $key);
        } else {
            $this->view->assign('key', 'vonMit');
        }
        
        if ($this->request->hasArgument('userKey')) {
            $userKey = $this->request->getArgument('userKey');
            $this->view->assign('userKey', $userKey);
        }
        
        // Search exist?
        if ($this->request->hasArgument('search')) {
            $search = $this->request->getArgument('search');
        }

        // Search status?
        if ($this->request->hasArgument('searchstatus')) {
            $searchstatus = $this->request->getArgument('searchstatus');            
            if ($searchstatus == "delete") {
                $search = "";
            }
        }
        
        //$aktuser = new Mitarbeiter();
        $aktuser = $this->objectManager->
                get('Pmwebdesign\\Staffm\\Domain\\Repository\\MitarbeiterRepository')->
                findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        if ($aktuser != NULL) {
            $this->view->assign('aktuser', $aktuser);
        }
        $this->view->assign('searchstatus', $searchstatus);
        $this->view->assign('search', $search);
        $this->view->assign('mitarbeiter', $mitarbeiter);
    }

    /**
     * Show the details of an employee from the custom list
     * 
     * @param int $mitarbeiter	
     * @return void
     */
    public function showCustomAction($mitarbeiter)
    {
        $mitarbeiter = $this->objectManager->get('Pmwebdesign\\Staffm\\Domain\\Repository\\MitarbeiterRepository')->findByUid($mitarbeiter);
        $this->view->assign('mitarbeiter', $mitarbeiter);
    }

    /**
     * Assign a responsible to a cost center
     * 
     * @param Kostenstelle $kostenstelle
     * @param Mitarbeiter $mitarbeiter
     * @return Mitarbeiter
     */
    public function chooseAction(Kostenstelle $kostenstelle, Mitarbeiter $mitarbeiter)
    {
        $this->addFlashMessage('Kostenstellenverantwortlichen "'.$mitarbeiter->getLastName().' '.$mitarbeiter->getFirstName().'" zugewiesen!', '', AbstractMessage::OK);
        $kostenstelle->setVerantwortlicher($mitarbeiter);
        $this->objectManager->get('Pmwebdesign\\Staffm\\Domain\\Repository\\KostenstelleRepository')->update($kostenstelle);
        $this->persistenceManager->persistAll();

        // Delete Caches
        $cacheService = GeneralUtility::makeInstance(\Pmwebdesign\Staffm\Domain\Service\CacheService::class);
        $cacheService->deleteCaches($kostenstelle->getBezeichnung(), "list", "Kostenstelle", 0);
        $cacheService->deleteCaches($kostenstelle->getBezeichnung(), "show", "Kostenstelle", $kostenstelle->getUid());

        $this->redirect('edit', 'Kostenstelle', NULL, array('kostenstelle' => $kostenstelle));
    }

    /**
     * New employee form
     * 
     * @param Mitarbeiter $newMitarbeiter
     * @ignorevalidation $newMitarbeiter
     * @return void
     */
    public function newAction(Mitarbeiter $newMitarbeiter = NULL)
    {
        $this->view->assign('newMitarbeiter', $newMitarbeiter);
    }

    /**
     * Creates the new employee
     * 
     * @param Mitarbeiter $newMitarbeiter
     * @return void
     */
    public function createAction(Mitarbeiter $newMitarbeiter)
    {
        $this->addFlashMessage('Mitarbeiter angelegt!', '', AbstractMessage::OK);
        $this->mitarbeiterRepository->add($newMitarbeiter);
        $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager')->persistAll();

        // Delete Caches        
        $cacheService = GeneralUtility::makeInstance(\Pmwebdesign\Staffm\Domain\Service\CacheService::class);
        $cacheService->deleteCaches($newMitarbeiter->getLastName(), $this->request->getControllerActionName(), $this->request->getControllerName(), 0);

        $this->redirect('edit', 'Mitarbeiter', NULL, array('mitarbeiter' => $newMitarbeiter));
    }

    /**
     * Edit form for employee
     * 
     * @param Mitarbeiter $mitarbeiter     
     * @ignorevalidation $mitarbeiter
     * @return void
     */
    public function editAction(Mitarbeiter $mitarbeiter)
    {          
        if($this->request->hasArgument('aktuser')) {            
            $aktuser = $this->objectManager->get('Pmwebdesign\\Staffm\\Domain\\Repository\\MitarbeiterRepository')->findByUid($this->request->getArgument('aktuser'));           
            $this->view->assign('aktuser', $aktuser);
        }
        
        // Logged in user              
        $aktuser = $this->objectManager->
                get('Pmwebdesign\\Staffm\\Domain\\Repository\\MitarbeiterRepository')->
                findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        if ($aktuser) {
            $this->view->assign('aktuser', $aktuser);
        } else {
            $aktuser = $this->objectManager->
                get('Pmwebdesign\\Staffm\\Domain\\Repository\\MitarbeiterRepository')->
                findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
            if($aktuser != NULL)  {
                $this->view->assign('aktuser', $aktuser);
            }
        }

        // Search exist?
        if ($this->request->hasArgument('search')) {
            $search = $this->request->getArgument('search');
            $this->view->assign('search', $search);
        }

        // Supervisor editing?
        if ($this->request->hasArgument('berechtigung')) {
            $berechtigung = $this->request->getArgument('berechtigung');
            $this->view->assign('berechtigung', $berechtigung);
        }        
        
        if ($this->request->hasArgument('userKey')) {
            $userKey = $this->request->getArgument('userKey');
            $this->view->assign('userKey', $userKey);
        }        
        
        if ($this->request->hasArgument('key')) {
            $key = $this->request->getArgument('key');
            $this->view->assign('key', $key);
        }             
        
        if ($this->request->hasArgument('art')) {
            $art = $this->request->getArgument('art');
            $this->view->assign('art', $art);
        }             
        
        if ($userKey == 'auswahlVgs') { 
            $berechtigung = "vonVorg";
            $this->view->assign('berechtigung', $berechtigung);
        }                 
        
        $this->view->assign('mitarbeiter', $mitarbeiter);
    }

    /**
     * Form edit for employee
     * 
     * @param Mitarbeiter $mitarbeiter
     * @ignorevalidation $mitarbeiter
     * @return void
     */
    public function editUserAction(Mitarbeiter $mitarbeiter)
    {
        // Search exist?
        if ($this->request->hasArgument('search')) {
            $search = $this->request->getArgument('search');
            $this->view->assign('search', $search);
        }

        if ($this->request->hasArgument('key')) {
            $search = $this->request->getArgument('key');
            $this->view->assign('key', $search);
        }

        $this->view->assign('mitarbeiter', $mitarbeiter);
    }

    /**
     * Form edit for employee
     * 
     * @param Mitarbeiter $ma
     * @param Position $position
     * @param Kostenstelle $kostenstelle
     * @param Firma $firma
     * @param Qualifikation $qualifikation
     * @return void
     */
    public function editKstAction(Mitarbeiter $ma, Position $position = NULL, Kostenstelle $kostenstelle = NULL, Firma $firma = NULL, Qualifikation $qualifikation = NULL)
    {
        if ($this->request->hasArgument('kst')) {
            $kst = $this->request->getArgument('kst');
            $this->view->assign('kst', $kst);
        }
        
        if ($this->request->hasArgument('key')) {
            $key = $this->request->getArgument('key');
            $this->view->assign('key', $key);
        }
        
         // Logged in user              
        $aktuser = $this->objectManager->
                get('Pmwebdesign\\Staffm\\Domain\\Repository\\MitarbeiterRepository')->
                findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        if ($aktuser) {
            $this->view->assign('aktuser', $aktuser);
        } else {
            $aktuser = $this->objectManager->
                get('Pmwebdesign\\Staffm\\Domain\\Repository\\MitarbeiterRepository')->
                findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
            if($aktuser != NULL)  {
                $this->view->assign('aktuser', $aktuser);
            }
        }

        $this->view->assign('mitarbeiter', $ma);
        $this->view->assign('position', $position);
        $this->view->assign('kostenstelle', $kostenstelle);
        $this->view->assign('firma', $firma);
        $this->view->assign('qualifikation', $qualifikation);
    }

    /**
     * Update an employee
     * 
     * @param Mitarbeiter $mitarbeiter	 
     * @return void
     */
    public function updateAction(Mitarbeiter $mitarbeiter)
    {
        // Get assigned qualifications
        $changestatusQualifications = FALSE;
        if ($this->request->hasArgument('qualifikationen')) {
            // QualificationService
            $qualificationService = GeneralUtility::makeInstance(QualificationService::class);
            $mitarbeiter->setEmployeequalifications($qualificationService->getEmployeequalificationsFromEmployee($this->request, $this->objectManager, $mitarbeiter));
            $changestatusQualifications = TRUE;
        }         
        // Get assigned categories
        if ($this->request->hasArgument('categories')) {     
            /* @var $categoryService \Pmwebdesign\Staffm\Domain\Service\CategoryService */
            $categoryService = GeneralUtility::makeInstance(\Pmwebdesign\Staffm\Domain\Service\CategoryService::class);           
            $mitarbeiter->setCategories($categoryService->getCategories($this->request, $this->objectManager));
        }
        
        /* @var $userService \Pmwebdesign\Staffm\Domain\Service\UserService */
        $userService = GeneralUtility::makeInstance(\Pmwebdesign\Staffm\Domain\Service\UserService::class);
        // Check if list fields have changed
        $changestatusList = $userService->getChangeStatus($mitarbeiter);
        
        $this->mitarbeiterRepository->update($mitarbeiter);
        $this->addFlashMessage('Der Mitarbeiter "'.$mitarbeiter->getFirstName().' '.$mitarbeiter->getLastName().'" wurde aktualisiert!', '', AbstractMessage::OK);

        $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager')->persistAll();

        /* @var $cacheService \Pmwebdesign\Staffm\Domain\Service\CacheService */
        $cacheService = GeneralUtility::makeInstance(\Pmwebdesign\Staffm\Domain\Service\CacheService::class);
        
        // Delete Caches           
        if($changestatusList == TRUE) {  
            $cacheService->deleteCaches($mitarbeiter->getLastName(), "list", $this->request->getControllerName(), 0);  
        }        
        if($changestatusQualifications == TRUE) {
            /* @var $employeequalification \Pmwebdesign\Staffm\Domain\Model\Employeequalification */
            foreach ($mitarbeiter->getEmployeequalifications() as $employeequalification) {    
                $cacheService->deleteCaches($employeequalification->getQualification()->getBezeichnung(), "list", ClassUtility::getShortClassNameFromObject($employeequalification->getQualification()), 0);  
            }    
        }

        if ($this->request->hasArgument('key')) {
            $key = $this->request->getArgument('key');
        } else {
            $key = "";
        }
        
        if ($this->request->hasArgument('userKey')) {
            $userKey = $this->request->getArgument('userKey');
        } else {
            $userKey = "";
        }
        
        if ($this->request->hasArgument('search')) {
            $search = $this->request->getArgument('search');
        }

        if ($this->request->hasArgument('kst')) {
            $kst = $this->request->getArgument('kst');
        }

        if ($this->request->hasArgument('berechtigung')) {
            $berechtigung = $this->request->getArgument('berechtigung');
        }
        
        if ($this->request->hasArgument('art')) {
            $art = $this->request->getArgument('art');           
        }                     
        if ($kst == 'kst') {
            $this->redirect('editKst', 'Mitarbeiter', NULL, array('ma' => $mitarbeiter, 'kostenstelle' => $mitarbeiter->getKostenstelle()));
        } else {
            if ($key == 'auswahlUsr' || $userKey == 'auswahlUsr') {
                $this->redirect('edit', 'Mitarbeiter', NULL, array('mitarbeiter' => $mitarbeiter, 'search' => $search, 'kst' => $kst, 'berechtigung' => $berechtigung, 'key' => $key, 'userKey' => $userKey, 'art' => $art));
            } elseif ($userKey == 'Vgs' || $userKey == 'auswahlVgs') { 
                $berechtigung = "vonVorg";
                $this->redirect('edit', 'Mitarbeiter', NULL, array('mitarbeiter' => $mitarbeiter, 'search' => $search, 'kst' => $kst, 'berechtigung' => $berechtigung, 'userKey' => $userKey, 'art' => $art));
            } else {
                $this->redirect('edit', 'Mitarbeiter', NULL, array('mitarbeiter' => $mitarbeiter, 'search' => $search, 'kst' => $kst, 'berechtigung' => $berechtigung, 'key' => $key, 'userKey' => $userKey, 'art' => $art));
            }
        }
    }

    /**
     * Deletes an employee
     * 
     * @param Mitarbeiter $mitarbeiter
     * @return void
     */
    public function deleteAction(Mitarbeiter $mitarbeiter)
    {
        $this->addFlashMessage('Mitarbeiter "' . $mitarbeiter->getFirstName() . ' ' . $mitarbeiter->getLastName() . '" wurde gelöscht!', '', AbstractMessage::ERROR);

        // Set employee as deleted
        $this->mitarbeiterRepository->remove($mitarbeiter);

        // Delete Caches        
        $cacheService = GeneralUtility::makeInstance(\Pmwebdesign\Staffm\Domain\Service\CacheService::class);
        $cacheService->deleteCaches($mitarbeiter->getLastName(), "list", $this->request->getControllerName(), 0);
        $cacheService->deleteCaches($mitarbeiter->getLastName(), "show", $this->request->getControllerName(), $mitarbeiter->getUid());
        /* @var $employeequalification \Pmwebdesign\Staffm\Domain\Model\Employeequalification */
        foreach ($mitarbeiter->getEmployeequalifications() as $employeequalification) {    
            $cacheService->deleteCaches($employeequalification->getQualification()->getBezeichnung(), "list", ClassUtility::getShortClassNameFromObject($employeequalification->getQualification()), 0);  
        }  

        $this->redirect('list', 'Mitarbeiter', NULL, array('cache' => 'notcache'));
    }

    /**
     * Delete qualifications of an employee
     * 
     * @param Mitarbeiter $mitarbeiter
     * @return void 
     */
    public function deleteQualiAction(Mitarbeiter $mitarbeiter)
    {
        $this->addFlashMessage('Alle Qualifikationen vom Mitarbeiter "' . $mitarbeiter->getFirstName() . ' ' . $mitarbeiter->getLastName() . '" wurden gelöscht!', '', AbstractMessage::ERROR);

        if ($this->request->hasArgument('search')) {
            $search = $this->request->getArgument('search');
        }

        // Supervisor editing?
        if ($this->request->hasArgument('berechtigung')) {
            $berechtigung = $this->request->getArgument('berechtigung');
            $this->view->assign('berechtigung', $berechtigung);
        }        
        
        if ($this->request->hasArgument('userKey')) {
            $userKey = $this->request->getArgument('userKey');
            $this->view->assign('userKey', $userKey);
        }        
        
        if ($this->request->hasArgument('key')) {
            $key = $this->request->getArgument('key');
            $this->view->assign('key', $key);
        }             
        
        if ($userKey == 'auswahlVgs') { 
            $berechtigung = "vonVorg";
            $this->view->assign('berechtigung', $berechtigung);
        } 
        
        if ($this->request->hasArgument('search')) {
            $search = $this->request->getArgument('search');
        }

        if ($this->request->hasArgument('kst')) {
            $kst = $this->request->getArgument('kst');
        }

        if ($this->request->hasArgument('berechtigung')) {
            $berechtigung = $this->request->getArgument('berechtigung');
        }
        
        if ($this->request->hasArgument('art')) {
            $art = $this->request->getArgument('art');           
        }     
        
        $employeequalifications = new ObjectStorage();
        $mitarbeiter->setEmployeequalifications($employeequalifications);
        $this->mitarbeiterRepository->update($mitarbeiter);
        
        /* @var $employeequalification \Pmwebdesign\Staffm\Domain\Model\Employeequalification */
        foreach ($mitarbeiter->getEmployeequalifications() as $employeequalification) {    
            $cacheService->deleteCaches($employeequalification->getQualification()->getBezeichnung(), "list", ClassUtility::getShortClassNameFromObject($employeequalification->getQualification()), 0);  
        }  
        
        $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager')->persistAll();
        $this->redirect('edit', 'Mitarbeiter', NULL, array('mitarbeiter' => $mitarbeiter, 'search' => $search, 'berechtigung' => $berechtigung, 'key' => $key, 'userKey' => $userKey, 'art' => $art));
    }
    
    /**
     * Delete categories of an employee
     * 
     * @param Mitarbeiter $employee
     */
    public function deleteCategoriesAction(Mitarbeiter $employee)
    {
        if ($this->request->hasArgument('search')) {
            $search = $this->request->getArgument('search');
        }
        if ($this->request->hasArgument('berechtigung')) {
            $berechtigung = $this->request->getArgument('berechtigung');
        }
        $employee->setCategories(new ObjectStorage());
        $this->mitarbeiterRepository->update($employee);
        $this->addFlashMessage('Alle Kategorien vom Mitarbeiter "' . $employee->getFirstName() . ' ' . $employee->getLastName() . '" wurden gelöscht!', '', AbstractMessage::ERROR);
        $this->redirect('edit', 'Mitarbeiter', NULL, array('mitarbeiter' => $employee, 'search' => $search, 'berechtigung' => $berechtigung));
    }
    
    /**
     * Delete representations of an employee
     * 
     * @param Mitarbeiter $employee
     */
    public function deleteRepresentationsAction(Mitarbeiter $employee)
    {
        if ($this->request->hasArgument('search')) {
            $search = $this->request->getArgument('search');
        }
        if ($this->request->hasArgument('berechtigung')) {
            $berechtigung = $this->request->getArgument('berechtigung');
        }
        if ($this->request->hasArgument('userKey')) {
            $userKey = $this->request->getArgument('userKey');
        }       
        if ($this->request->hasArgument('art')) {
            $art = $this->request->getArgument('art');        
        }    
        
        $employee->setRepresentations(new ObjectStorage());
        $this->mitarbeiterRepository->update($employee);
        $this->addFlashMessage('Alle Vertreter vom Mitarbeiter "' . $employee->getFirstName() . ' ' . $employee->getLastName() . '" wurden gelöscht!', '', AbstractMessage::ERROR);
        $this->redirect('edit', 'Mitarbeiter', NULL, array('mitarbeiter' => $employee, 'search' => $search, 'berechtigung' => $berechtigung, 'key' => $userKey, 'art' => $art));
    }
    
    /**
     * Delete costcenter of a representation from a employee
     *   
     * @param \Pmwebdesign\Staffm\Domain\Model\Representation $representation
     */
    public function deleteRepresentationCostCentersAction(\Pmwebdesign\Staffm\Domain\Model\Representation $representation)
    {
        if ($this->request->hasArgument('search')) {
            $search = $this->request->getArgument('search');
        }
        if ($this->request->hasArgument('berechtigung')) {
            $berechtigung = $this->request->getArgument('berechtigung');
        }
        if ($this->request->hasArgument('key')) {
            $userKey = $this->request->getArgument('key');
        }        
        if ($this->request->hasArgument('userKey')) {
            $userKey = $this->request->getArgument('userKey');
        }        
        $employee = $representation->getEmployee();        
        $representations = $employee->getRepresentations();
        foreach ($representations as $rep) {
            if($rep == $representation) {
                $rep->setCostcenters(new \TYPO3\CMS\Extbase\Persistence\ObjectStorage());
                break;
            }
        }
        $employee->setRepresentations($representations);
        $this->mitarbeiterRepository->update($employee);  
        
        $this->addFlashMessage('Die ausgenommenen Kostenstellen für Vertreter "' . $representation->getDeputy()->getFirstName() . ' ' . $representation->getDeputy()->getLastName() . '" wurden entfernt!', '', AbstractMessage::ERROR);
        $this->redirect('edit', 'Mitarbeiter', NULL, array('mitarbeiter' => $employee, 'search' => $search, 'berechtigung' => $berechtigung, 'key' => $key, 'userKey' => $userKey));
    }

    /**
     * Set TypeConverter option for image upload
     */
    public function initializeCreateAction()
    {
        $this->setTypeConverterConfigurationForImageUpload('newMitarbeiter');
    }

    /**
     * Set TypeConverter option for image upload
     */
    public function initializeUpdateAction()
    {
        $this->setTypeConverterConfigurationForImageUpload('mitarbeiter');
    }

    /**
     * Configuration for Image Upload
     */
    protected function setTypeConverterConfigurationForImageUpload($argumentName)
    {
        $uploadConfiguration = [
            UploadedFileReferenceConverter::CONFIGURATION_ALLOWED_FILE_EXTENSIONS => 'jpg',
            UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER => '1:/user_upload/profilbilder/',
        ];
        /** @var PropertyMappingConfiguration $newMitarbeiterConfiguration */
        $newMitarbeiterConfiguration = $this->arguments[$argumentName]->getPropertyMappingConfiguration();

        // Convert all images
        for ($i = 0; $i < 99; $i++) {
            $newMitarbeiterConfiguration->forProperty('image.' . $i)
                    ->setTypeConverterOptions(
                            'Pmwebdesign\\Staffm\\Property\\TypeConverter\\UploadedFileReferenceConverter', $uploadConfiguration
            );
        }
    }
    
    /**
     * Set Representations
     * Just Deputies, excluded cost centers have another action
     * 
     * @param Mitarbeiter $employee
     * @return void
     */
    public function setRepresentationsAction(Mitarbeiter $employee)
    {
        if ($this->request->hasArgument('search')) {
            $search = $this->request->getArgument('search');
        }
        if ($this->request->hasArgument('berechtigung')) {
            $berechtigung = $this->request->getArgument('berechtigung');
        }
        if ($this->request->hasArgument('userKey')) {
            $userKey = $this->request->getArgument('userKey');
        }        
        
        if ($this->request->hasArgument('art')) {
            $art = $this->request->getArgument('art');
        }    
        
        // Assigned deputies?
        if ($this->request->hasArgument('employees')) {
            /* @var $userService \Pmwebdesign\Staffm\Domain\Service\UserService */
            $userService = GeneralUtility::makeInstance(\Pmwebdesign\Staffm\Domain\Service\UserService::class);           
            $employee->setRepresentations($userService->getRepresentations($this->request, $this->objectManager, $employee));
        }
        $this->mitarbeiterRepository->update($employee);
        
        // Send Email information to deputies
        /* @var $mailService \Pmwebdesign\Staffm\Domain\Service\MailService */
        $mailService = GeneralUtility::makeInstance(\Pmwebdesign\Staffm\Domain\Service\MailService::class);           
        
        // Previous Representations
        $previousRepresentations = $employee->_getCleanProperty('representations');
        
        /* @var $representation \Pmwebdesign\Staffm\Domain\Model\Representation */
        foreach ($employee->getRepresentations() as $representation) {  
            $found = false;
            foreach ($previousRepresentations as $previousRepresentation) {
                // Deputy available exist?
                if($representation->getDeputy()->getUid() == $previousRepresentation->getDeputy()->getUid()) {
                    // Yes
                    $found = true;
                }
            }
            // Deputy not found?
            if($found == false) {
                $textQualification = "";
                // Qualification authorization?
                if($representation->getQualificationAuthorization() == true) {
                    // Yes, set text
                    $textQualification = "\n\nEs wird Dir außerdem hiermit die Berechtigung übertragen, die Mitarbeiter meiner Kostenstellen zu bearbeiten (z. B. Qualifikationen).";
                }
                
                // Has deputy a email address?
                if($representation->getDeputy()->getEmail() != "" && $representation->getDeputy()->getEmail() != null) {
                    // Send email to new deputy
                    $message = "Hallo ".$representation->getDeputy()->getFirstName().",\n\nich habe Dich im Intranet als Vertreter ".
                            "eingestellt." . $textQualification .
                            "\n\nFreundliche Grüße,\n\n".$employee->getFirstName()." ".$employee->getLastName();  
                    $mailService->sendEmail($employee->getEmail(), $employee->getLastName()." ".$employee->getFirstName(), $representation->getDeputy()->getEmail(), $representation->getDeputy()->getLastName()." ".$representation->getDeputy()->getFirstName(), "Intranet Vertretung", $message);
                }
            }
            
        }        
        
        $this->addFlashMessage('Die Vertreter vom Mitarbeiter "' . $employee->getFirstName() . ' ' . $employee->getLastName() . '" wurden aktualisiert!', '', AbstractMessage::OK);
        $this->redirect('edit', 'Mitarbeiter', NULL, array('mitarbeiter' => $employee, 'search' => $search, 'berechtigung' => $berechtigung, 'key' => $userKey, 'art' => $art));
    }
    
    /**
     * Set employee presence status
     */
    public function setEmployeePresentStatusAction()
    {
        $employeeUid = $this->request->getArgument('employeeUid');        
        $cb = $this->request->getArgument('cbStatus');
        if($cb == "true") {
            $cb = 1;
        } else {
            $cb = 0;
        }
        
        /* @var $employee \Pmwebdesign\Staffm\Domain\Model\Mitarbeiter */
        $employee = $this->mitarbeiterRepository->findByUid($employeeUid);
        $employee->setPresent($cb);
        $this->mitarbeiterRepository->update($employee);   
        
        return "".$cb;
    }    
    
    /**
     * Set the exempted cost centers for a Deputy
     * 
     * @param \Pmwebdesign\Staffm\Domain\Model\Representation $representation
     */
    public function setRepresentationCostCentersAction(\Pmwebdesign\Staffm\Domain\Model\Representation $representation)
    {
        if ($this->request->hasArgument('search')) {
            $search = $this->request->getArgument('search');
        }
        if ($this->request->hasArgument('berechtigung')) {
            $berechtigung = $this->request->getArgument('berechtigung');
        }
        if ($this->request->hasArgument('key')) {
            $userKey = $this->request->getArgument('key');
        }        
        if ($this->request->hasArgument('userKey')) {
            $userKey = $this->request->getArgument('userKey');
        }        
        
        if ($this->request->hasArgument('art')) {
            $art = $this->request->getArgument('art');            
        }    
        
        $employee = $representation->getEmployee();
        
        // Assigned costcenters for deputies?
        if ($this->request->hasArgument('costcenters')) {            
            /* @var $costCenterService \Pmwebdesign\Staffm\Domain\Service\CostCenterService */
            $costCenterService = GeneralUtility::makeInstance(\Pmwebdesign\Staffm\Domain\Service\CostCenterService::class);  
            
            /* @var $r \Pmwebdesign\Staffm\Domain\Model\Representation */
            $representations = $employee->getRepresentations();
            foreach ($representations as $r) {
                if($representation == $r) {                    
                    $r->setCostcenters($costCenterService->getCostCenters($this->request, $this->objectManager)); 
                    break;
                }
            }            
            $employee->setRepresentations($representations);
        }
        
        $this->mitarbeiterRepository->update($employee);       
        $this->addFlashMessage('Die ausgenommenen Kostenstellen für Vertreter "' . $representation->getDeputy()->getFirstName() . ' ' . $representation->getDeputy()->getLastName() . '" wurden aktualisiert!', '', AbstractMessage::OK);
        $this->redirect('edit', 'Mitarbeiter', NULL, array('mitarbeiter' => $employee, 'search' => $search, 'berechtigung' => $berechtigung, 'key' => $key, 'userKey' => $userKey, 'art' => $art));
    }

    /**
     * Overwrite initialize action 
     * 
     * @return void 
     */
    public function initializeAction()
    {
        parent::initializeAction();

        /* Caching Framework */
        $this->cache = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->getCache('staffm_mycache');
    }
    
    /**
     * Lists all fe users who have no username.
     */
    public function listCreateAction() 
    {
        $allUsers = $this->mitarbeiterRepository->findAll();
        $userWithoutUsername = new ObjectStorage();
        foreach($allUsers as $user) {
            if($user->getUsername() == '') {
                $userWithoutUsername->attach($user);
            }
        }
        $this->view->assign('users', $userWithoutUsername);
    }
    
    /**
     * Removes apostrophes from a string.
     * Source: https://github.com/WordPress/WordPress/blob/a2693fd8602e3263b5925b9d799ddd577202167d/wp-includes/formatting.php#L1528
     * 
     * @param string $string
     * @return string
     */
    private function removeAccents($string) {
        if(!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }
        
        $chars = array(
        // Decompositions for Latin-1 Supplement
        chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
        chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
        chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
        chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
        chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
        chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
        chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
        chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
        chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
        chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
        chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
        chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
        chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
        chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
        chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
        chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
        chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
        chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
        chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
        chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
        chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
        chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
        chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
        chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
        chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
        chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
        chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
        chr(195).chr(191) => 'y',
        // Decompositions for Latin Extended-A
        chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
        chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
        chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
        chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
        chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
        chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
        chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
        chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
        chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
        chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
        chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
        chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
        chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
        chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
        chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
        chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
        chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
        chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
        chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
        chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
        chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
        chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
        chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
        chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
        chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
        chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
        chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
        chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
        chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
        chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
        chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
        chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
        chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
        chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
        chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
        chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
        chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
        chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
        chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
        chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
        chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
        chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
        chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
        chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
        chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
        chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
        chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
        chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
        chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
        chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
        chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
        chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
        chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
        chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
        chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
        chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
        chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
        chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
        chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
        chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
        chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
        chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
        chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
        chr(197).chr(190) => 'z', chr(197).chr(191) => 's'
        );

        $string = strtr($string, $chars);

        return $string;
    }
    /**
     * Shows a user wwith his username and password.
     */
    public function editCreateAction()
    {
        $userUid = $this->request->getArgument('userUid');
        $user = $this->mitarbeiterRepository->findByUid($userUid);
        $lastName = $this->removeAccents($user->getLastName());
        $firstName = $this->removeAccents($user->getFirstName());
        
        $username = $user->getPersonalnummer() . substr($firstName, 0, 2) . substr($lastName, 0, 2);
        $password = bin2hex(random_bytes(3));
        
        $allKostenstellen = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\KostenstelleRepository::class)->findAll();
        
        $this->view->assign('username', $username);
        $this->view->assign('password', $password);
        $this->view->assign('userCreate', $user);
        $this->view->assign('kostenstellen', $allKostenstellen);
    }
    
    /**
     * Saves the the new data of the user.
     */
    public function saveUserDataAction()
    {
        $userUid = $this->request->getArgument('userUid');
        $user = $this->mitarbeiterRepository->findByUid($userUid);
        
        if($this->request->getArgument('mail') != '') {
            $user->setEmail($this->request->getArgument('mail'));
        }
        if($this->request->getArgument('kostenstelleApps') != '') {
            $number = substr($this->request->getArgument('kostenstelleApps'), 0, 4);
            $appCostCenter = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\KostenstelleRepository::class)->findByNummer($number);
            $user->setAppCostCenter($appCostCenter);
            $date = new \DateTime($this->request->getArgument('dateExpiry'));
            $date->add(new \DateInterval('P1D'));
            $user->setExpiryDate($date);
        }
        if($this->request->getArgument('title') != '') {
            $user->setTitle($this->request->getArgument('title'));
        }
        if($this->request->getArgument('position') != '') {
            $position = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\PositionRepository::class)->findSearchForm($this->request->getArgument('position'), 1)[0];
            $user->setPosition($position);
        }
        $user->setUsername($this->request->getArgument('username'));
        $user->setName($user->getLastName() . ' ' . $user->getFirstName());
        $password = $this->request->getArgument('password');
        $hashInstance = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory::class)->getDefaultHashInstance('FE');
        $hashedPassword = $hashInstance->getHashedPassword($password);
        $user->setPassword($hashedPassword);
        $userGroups = new ObjectStorage();
        $userGroups->attach($this->objectManager->get(\TYPO3\CMS\Extbase\Domain\Repository\FrontendUserGroupRepository::class)->findByUid(1));
        $user->setUsergroup($userGroups);
        
        $this->mitarbeiterRepository->update($user);
        
        $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class)->persistAll();
        
        $this->addFlashMessage(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_staffm_saved.data', 'staffm'));
        
        $this->forward('listCreated');
    }
    
    /**
     * List all created users.
     */
    public function listCreatedAction()
    {
        $allUsers = $this->mitarbeiterRepository->findAll();
        
        $createdUsers = new ObjectStorage();
        foreach($allUsers as $user) {
            $firstName = $this->removeAccents($user->getFirstName());
            $lastName = $this->removeAccents($user->getLastName());
            $createdUsername = $user->getPersonalnummer() . substr($firstName, 0, 2) . substr($lastName, 0, 2);
            $username = $user->getUsername();
            if($createdUsername == $username) {
                $createdUsers->attach($user);
            }
        }
        
        $this->view->assign('createdUsers', $createdUsers);
    }
    
    /**
     * Removes the username, password and usergroups of a user.
     */
    public function removeUsernameAction()
    {
        $user = $this->mitarbeiterRepository->findByUid($this->request->getArgument('userUid'));
        $user->setUsername('');
        $user->setPassword('');
        $user->setName('');
        $user->setUsergroup(new ObjectStorage());
        
        $this->mitarbeiterRepository->update($user);
        
        $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class)->persistAll();
        
        $this->forward('listCreated');
    }
    
    /**
     * Assigns the user the new mail address.
     */
    public function assignNewMailAction()
    {
        $user = $this->mitarbeiterRepository->findByUid($this->request->getArgument('userUid'));
        $mail = $this->request->getArgument('mail');
        $user->setEmail($mail);
        $this->mitarbeiterRepository->update($user);
        $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class)->persistAll();
        
        $this->addFlashMessage(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_staffm_new.mail.assigned', 'staffm'));
        
        $this->forward('listCreated');
    }
    
    /**
     * Removes the mail address of the user.
     */
    public function removeMailAction()
    {
        $user = $this->mitarbeiterRepository->findByUid($this->request->getArgument('userUid'));
        $user->setEmail('');
        $this->mitarbeiterRepository->update($user);
        $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class)->persistAll();
        
        $this->addFlashMessage(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_staffm_removed.mail', 'staffm'));
        
        $this->forward('listCreated');
    }
    
    /**
     * Sets the new password of the user.
     */
    public function assignNewPasswordAction()
    {
        $user = $this->mitarbeiterRepository->findByUid($this->request->getArgument('userUid'));
        
        $password = $this->request->getArgument('password');
        $hashInstance = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory::class)->getDefaultHashInstance('FE');
        $hashedPassword = $hashInstance->getHashedPassword($password);
        
        $user->setPassword($hashedPassword);
        
        $this->mitarbeiterRepository->update($user);
        $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class)->persistAll();
        
        $this->addFlashMessage(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_staffm_new.password.assigned', 'staffm'));
        $this->forward('listCreated');
    }
    
    /**
     * Form to create a new user.
     */
    public function createNewUserAction()
    {
        $kostenstellen = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\KostenstelleRepository::class)->findAll();
        $this->view->assign('kostenstellen', $kostenstellen);
        
        $password = bin2hex(random_bytes(3));
        $this->view->assign('password', $password);
        
        $positionen = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\PositionRepository::class)->findAll();
        $this->view->assign('positionen', $positionen);
        
        $firmen = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\FirmaRepository::class)->findAll();
        $this->view->assign('firmen', $firmen);
    }
    
    /**
     * Returns all cost centers as string.
     * @return string
     */
    public function getAllCostCentersAction()
    {
        $allCostCenters = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\KostenstelleRepository::class)->findAll();
        $costCenters = '';
        foreach($allCostCenters as $center) {
           $costCenters .= $center->getNummer() . ' ' . $center->getBezeichnung() . ',';
        }
        return $costCenters;
    }
    
    /**
     * Returns all positions as string.
     * @return string
     */
    public function getAllPositionsAction()
    {
        $allPositions = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\PositionRepository::class)->findAll();
        $positions = '';
        foreach($allPositions as $pos) {
           $positions .= $pos->getBezeichnung() . ',';
        }
        return $positions;
    }
    
    /**
     * Returns all companies as string.
     * @return string
     */
    public function getAllCompaniesAction()
    {
        $allCompanies = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\FirmaRepository::class)->findAll();
        $companies = '';
        foreach($allCompanies as $com) {
           $companies .= $com->getBezeichnung() . ',';
        }
        return $companies;
    }
    
    /**
     * Creates a new user with the given params.
     */
    public function createUserAction() {
        $username = $this->request->getArgument('username');
        $password = $this->request->getArgument('password');
        $firstName = $this->request->getArgument('firstName');
        $lastName = $this->request->getArgument('lastName');
        $pnr = $this->request->getArgument('pnr');
        $kostenstelle = $this->request->getArgument('kostenstelle');
        $kostenstelleApps = $this->request->getArgument('kostenstelleApps');
        $dateExpiry = $this->request->getArgument('dateExpiry');
        $title = $this->request->getArgument('title');
        $position = $this->request->getArgument('position');
        $firma = $this->request->getArgument('firma');
        $mail = $this->request->getArgument('mail');
        
        $user = new Mitarbeiter();
        $user->setUsername($username);
        $hashInstance = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory::class)->getDefaultHashInstance('FE');
        $hashedPassword = $hashInstance->getHashedPassword($password);
        $user->setPassword($hashedPassword);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setPersonalnummer($pnr);
        $kostenstelleRep = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\KostenstelleRepository::class);
        $costNumber = substr($kostenstelle, 0, 4);
        $costCenter = $kostenstelleRep->findByNummer($costNumber);
        $user->setKostenstelle($costCenter);
        if($kostenstelleApps != '') {
            $appNumber = substr($kostenstelleApps, 0, 4);
            $appCostCenter = $kostenstelleRep->findByNummer($appNumber);
            $user->setAppCostCenter($appCostCenter);
            $date = new \DateTime($dateExpiry);
            $date->add(new \DateInterval('P1D'));
            $user->setExpiryDate($date);
        }
        if($title != '') {
            $user->setTitle($title);
        }
        if($position != '') {
            $positionRep = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\PositionRepository::class);
            $positionO = $positionRep->findSearchForm($position, 1)[0];
            $user->setPosition($positionO);
        }
        if($firma != '') {
            $firmaRep = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\FirmaRepository::class);
            $firmaO = $firmaRep->findSearchForm($firma, 1)[0];
            $user->setFirma($firmaO);
        }
        if($mail != '') {
            $user->setEmail($mail);
        }
        $user->setName($lastName . ' '. $firstName);
        
        $userGroups = new ObjectStorage();
        $userGroups->attach($this->objectManager->get(\TYPO3\CMS\Extbase\Domain\Repository\FrontendUserGroupRepository::class)->findByUid(1));
        $user->setUsergroup($userGroups);
        
        $this->mitarbeiterRepository->add($user);
        
        // first persist then the extbase_type can be set
        $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class)->persistAll();
        $this->mitarbeiterRepository->updateExtbaseType($user, 0);
        
        $this->addFlashMessage(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_staffm_new.user.successful', 'staffm'));
        
        $this->forward('listCreated');
    }
    
    public function editCreatedAction() 
    {
        $user = $this->mitarbeiterRepository->findByUid($this->request->getArgument('userUid'));
        $this->view->assign('userEdit', $user);
        
        $positionen = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\PositionRepository::class)->findAll();
        $this->view->assign('positionen', $positionen);
        
        $kostenstellen = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\KostenstelleRepository::class)->findAll();
        $this->view->assign('kostenstellen', $kostenstellen);
    }
    
    public function updateCreatedUserAction()
    {
        $user = $this->mitarbeiterRepository->findByUid($this->request->getArgument('userUid'));
        
        $appCostCenter = $this->request->getArgument('kostenstelleApps');
        $dateExpiry = $this->request->getArgument('dateExpiry');
        $title = $this->request->getArgument('title');
        $position = $this->request->getArgument('position');
        $mail = $this->request->getArgument('mail');
        
        if($appCostCenter != '') {
            $kostenstelleRep = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\KostenstelleRepository::class);
            $centerNumber = substr($appCostCenter, 0, 4);
            $center = $kostenstelleRep->findByNummer($centerNumber);
            $user->setAppCostCenter($center);
            $date = new \DateTime($dateExpiry);
            $date->add(new \DateInterval('P1D'));
            $user->setExpiryDate($date);
        } else {
            $user->removeAppCostCenter();
            $user->removeExpiryDate();
        }
        $user->setTitle($title);
        if($position != '') {
            $positionRep = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\PositionRepository::class);
            $positionObject = $positionRep->findSearchForm($position, 1)[0];
            $user->setPosition($positionObject);
        } else {
            $user->removePosition();
        }
        $user->setEmail($mail);
        
        $this->mitarbeiterRepository->update($user);
        $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class)->persistAll();
        
        $this->addFlashMessage(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_staffm_saved.data', 'staffm'));
        
        $this->forward('listCreated');
    }
    
    public function listAllUserAction()
    {
        $allUsers = $this->mitarbeiterRepository->findAll();
        $this->view->assign('allUsers', $allUsers);
        
        $costcenters = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\KostenstelleRepository::class)->findAll();
        $this->view->assign('costcenters', $costcenters);
    }
    
    public function updateAppCostCenterAction()
    {
        $user = $this->mitarbeiterRepository->findByUid($this->request->getArgument('userUid'));
        $appCostCenterString = $this->request->getArgument('appCostCenter');
        $expiryDateString = $this->request->getArgument('expiryDate');
        
        if($appCostCenterString == '') {
            $user->removeAppCostCenter();
            $user->removeExpiryDate();
        } else {
            $nummer = substr($appCostCenterString, 0, 4);
            $kostenstelle = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\KostenstelleRepository::class)->findByNummer($nummer);
            $user->setAppCostCenter($kostenstelle);
            $date = new \DateTime($expiryDateString);
            $date->add(new \DateInterval('P1D'));
            $user->setExpiryDate($date);
        }
        $this->mitarbeiterRepository->update($user);
        
        $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class)->persistAll();
        
//        $this->addFlashMessage(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_staffm_cost.center.assigned', 'staffm'));
        
//        $this->forward('listAllUser');
        return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_staffm_cost.center.assigned', 'staffm');
    }
    
    public function listAllUserModalAction() 
    {
        $user = $this->mitarbeiterRepository->findByUid($this->request->getArgument('userUid'));
        $this->view->assign('userL', $user);
    }
    
    public function deleteCreatedUserAction() 
    {
        $userId = $this->request->getArgument('userUid');
        $userToDelete = $this->mitarbeiterRepository->findByUid($userId);
        $this->mitarbeiterRepository->remove($userToDelete);
        
        $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class)->persistAll();
        
        $this->addFlashMessage(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_staffm_user_deleted1', 'staffm'). $userToDelete->getFirstName() . ' ' . $userToDelete->getLastName() . \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('tx_staffm_user_deleted2', 'staffm'));
        
        $this->forward('listCreated');
    }
    
    public function listChooseEmployeeAction(\Pmwebdesign\Staffm\Domain\Model\Representation $representation)
    {
        $this->view->assign('menuname', 'newRep');
        $superior = $representation->getEmployee();
        
        $costRep = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\Kostenstellerepository::class);
        $costCenters = $costRep->findCostCentersFromResponsible($superior);
        $allEmployees = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        foreach($costCenters as $cost) {
            $allEmployees->addAll($cost->getMitarbeiters());
        }
        $this->view->assign('employees', $allEmployees);
        $this->view->assign('representation', $representation);
    }
    
    /**
     * 
     * @param \Pmwebdesign\Staffm\Domain\Model\Representation $representation
     */
    public function setSelectedEmployeesAction(\Pmwebdesign\Staffm\Domain\Model\Representation $representation)
    {
        $selectedUids = $this->request->getArgument('selected');
        $selectedEmployees = new ObjectStorage();
        foreach($selectedUids as $selected) {
            $employee = $this->mitarbeiterRepository->findByUid($selected);
            $selectedEmployees->attach($employee);
        }
        $representation->setSelectedEmployees($selectedEmployees);
        $repRep = $this->objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\RepresentationRepository::class);
        $repRep->update($representation);
        $this->redirect('edit', null, null, ['mitarbeiter' => $representation->getEmployee()]);
    }
}
