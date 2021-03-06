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

namespace Pmwebdesign\Staffm\Domain\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Qualification Services
 *
 * @author Markus Puffer <m.puffer@pm-webdesign.eu>
 */
class QualificationService
{

    /**
     * Check assigned qualifications with status and notes for an employee
     * 
     * @param \TYPO3\CMS\Extbase\Mvc\Request $request
     * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
     * @param \Pmwebdesign\Staffm\Domain\Model\Mitarbeiter $mitarbeiter
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Pmwebdesign\Staffm\Domain\Model\Employeequalification>
     */
    public function getEmployeequalificationsFromEmployee(\TYPO3\CMS\Extbase\Mvc\Request $request, \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager, \Pmwebdesign\Staffm\Domain\Model\Mitarbeiter $mitarbeiter)
    {
        if ($request->hasArgument('qualifikationen')) {
            $employeequalifications = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();

            // Read checkboxes into array
            $qua = $request->getArgument('qualifikationen');

            // Read previous qualifications of employee            
            $prevEmployeequalifications = $mitarbeiter->getEmployeequalifications();

            // Read status
            if ($request->hasArgument('qualificationsstatus')) {
                $qualificationsstatus = $request->getArgument('qualificationsstatus');
            }
            
            // Read targetstatus
            if ($request->hasArgument('qualificationstargetstatus')) {
                $qualificationstargetstatus = $request->getArgument('qualificationstargetstatus');
            }
            
            // Read notes
            if ($request->hasArgument('qualificationsnotes')) {
                $qualificationsnotes = $request->getArgument('qualificationsnotes');
            }
            // Read reminder dates
            if ($request->hasArgument('qualificationsreminderdate')) {
                $qualificationsreminderdate = $request->getArgument('qualificationsreminderdate');
            }

            // Get logged in user
            $userService = GeneralUtility::makeInstance(\Pmwebdesign\Staffm\Domain\Service\UserService::class);
            $assessor = $userService->getLoggedInUser();

            // Set qualifications to array items
            foreach ($qua as $q) {                
                $employeequalification = new \Pmwebdesign\Staffm\Domain\Model\Employeequalification();
                $qualification = $objectManager->get(
                                'Pmwebdesign\\Staffm\\Domain\\Repository\\QualifikationRepository'
                        )->findByUid($q);
                $status = $qualificationsstatus[$qualification->getUid()];
                $targetstatus = $qualificationstargetstatus[$qualification->getUid()];
                $note = $qualificationsnotes[$qualification->getUid()];
                $reminderDate = $qualificationsreminderdate[$qualification->getUid()];

                // Check if previous qualification exist
                $histories = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
                $prevStatus = FALSE;
                /* @var $prevEmployeequalification \Pmwebdesign\Staffm\Domain\Model\Employeequalification */
                foreach ($prevEmployeequalifications as $prevEmployeequalification) {
                    if ($prevEmployeequalification->getQualification() === $qualification) {
                        $prevStatus = TRUE;
                        // Qualification exist, just update
                        if ($status != null) {
                            $histories = $prevEmployeequalification->getHistories();
                            // Status changed or no histories?
                            if ($prevEmployeequalification->getStatus() <> $status || count($histories) <= 0) {
                                // Status has changed?
                                if ($prevEmployeequalification->getStatus() <> $status) {
                                    // Yes, status has changed   
                                    // Check history
                                    foreach ($histories as $history) {
                                        // Old status?
                                        if ($history->getStatus() == $prevEmployeequalification->getStatus()) {
                                            // Set End Date                                        
                                            $history->setDateTo(new \DateTime());
                                        }
                                    }
                                    // Set new status
                                    $prevEmployeequalification->setStatus($status);
                                }
                                
                                // Add new history
                                $newHistory = new \Pmwebdesign\Staffm\Domain\Model\History();
                                $newHistory->setStatus($status);
                                $newHistory->setDateFrom(new \DateTime());
                                $newHistory->setAssessor($assessor);
                                $newHistory->setNote($note);
                                $histories->attach($newHistory);
                                
                                $prevEmployeequalification->setHistories($histories);
                            // Same status and current user is the same as history user?
                            } else if ($prevEmployeequalification->getStatus() == $status && count($histories) > 0) {
                                // Get last history
                                $sum = $histories->count();
                                $counter = 1;          
                                foreach ($histories as $history) {
                                    if($sum == $counter) {
                                        /* @var $lastHistory \Pmwebdesign\Staffm\Domain\Model\History */      
                                        $lastHistory = $history;
                                    }
                                    $counter++;
                                }       
                                
                                // Current user is the same as history user?
                                if($lastHistory->getAssessor() === $assessor) {
                                    // Update notice
                                    $lastHistory->setNote($note);                                   
                                }
                            }
                        }
                        if ($note != null) {
                            $prevEmployeequalification->setNote($note);
                        }
                        break;
                    }
                }

                // Previous employeequalification found?   
                if ($prevStatus == FALSE) {
                    // No, set new employeequalification
                    $employeequalification->setEmployee($mitarbeiter);
                    $employeequalification->setQualification($qualification);
                    // Status?
                    if ($status != null) {
                        $employeequalification->setStatus($status);
                    }
                    // Target status?
                    if ($status != null) {
                        $employeequalification->setTargetstatus($targetstatus);
                    }
                    // Notice?
                    if ($note != null) {
                        $employeequalification->setNote($note);
                    }
                    // Reminder date?
                    if ($reminderDate != null) {
                        $employeequalification->setReminderDate($reminderDate);
                    }
                    // Add new history               
                    $newHistory = new \Pmwebdesign\Staffm\Domain\Model\History();
                    $newHistory->setStatus($status);
                    $newHistory->setDateFrom(new \DateTime());
                    $newHistory->setAssessor($assessor);
                    $newHistory->setNote($note);
                    $histories->attach($newHistory);
                    $employeequalification->setHistories($histories);
                    $employeequalifications->attach($employeequalification);
                } else {
                    // Yes, update previous employeequalification    
                     // Target status?
                    if ($status != null) {
                        $prevEmployeequalification->setTargetstatus($targetstatus);
                    }                
                    // Notice?
                    if ($note != null) {
                        $prevEmployeequalification->setNote($note);
                    } else {
                        $prevEmployeequalification->setNote("");
                    }
                    // Reminder date?
                    if ($reminderDate != null) {
                        $prevEmployeequalification->setReminderDate($reminderDate);
                    } else {
                        $prevEmployeequalification->setReminderDate(NULL);
                    }
                    $employeequalifications->attach($prevEmployeequalification);
                }
            }
            return $employeequalifications;
        }
    }

    /**
     * Check assigned employees with status and notes for a qualification
     * 
     * @param \TYPO3\CMS\Extbase\Mvc\Request $request
     * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
     * @param \Pmwebdesign\Staffm\Domain\Model\Qualifikation $qualification
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Pmwebdesign\Staffm\Domain\Model\Employeequalification>
     */
    public function getEmployeequalificationsFromQualification(\TYPO3\CMS\Extbase\Mvc\Request $request, \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager, \Pmwebdesign\Staffm\Domain\Model\Qualifikation $qualification)
    {
        if ($request->hasArgument('mitarbeiters')) {
            $employeequalifications = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();

            // Read checkboxes into array
            $ma = $request->getArgument('mitarbeiters');

            // Read previous qualifications of employee            
            $prevEmployeequalifications = $qualification->getEmployeequalifications();

            // Read status
            if ($request->hasArgument('qualificationsstatus')) {
                $qualificationsstatus = $request->getArgument('qualificationsstatus');
            }
            // Read notes
            if ($request->hasArgument('qualificationsnotes')) {
                $qualificationsnotes = $request->getArgument('qualificationsnotes');
            }
            // Read reminder dates
            if ($request->hasArgument('qualificationsreminderdate')) {
                $qualificationsreminderdate = $request->getArgument('qualificationsreminderdate');
            }

            // Get logged in user
            $userService = GeneralUtility::makeInstance(\Pmwebdesign\Staffm\Domain\Service\UserService::class);
            $assessor = $userService->getLoggedInUser();

            // Set qualifications to array items
            foreach ($ma as $m) {                
                $employeequalification = new \Pmwebdesign\Staffm\Domain\Model\Employeequalification();
                $employee = $objectManager->get(
                                'Pmwebdesign\\Staffm\\Domain\\Repository\\MitarbeiterRepository'
                        )->findByUid($m);
                $status = $qualificationsstatus[$employee->getUid()];
                $note = $qualificationsnotes[$employee->getUid()];
                $reminderDate = $qualificationsreminderdate[$employee->getUid()];

                // Check if previous qualification exist
                $histories = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
                $prevStatus = FALSE;
                foreach ($prevEmployeequalifications as $prevEmployeequalification) {                   
                    if ($prevEmployeequalification->getEmployee() === $employee) {
                        $prevStatus = TRUE;
                        // Employee exist, just update
                        if ($status != null) {
                            $histories = $prevEmployeequalification->getHistories();
                            // Status changed or no histories?
                            if ($prevEmployeequalification->getStatus() <> $status || count($histories) <= 0) {
                                // Status has changed?
                                if ($prevEmployeequalification->getStatus() <> $status) {
                                    // Yes, status has changed   
                                    // Check history
                                    foreach ($histories as $history) {
                                        // Old status?
                                        if ($history->getStatus() == $prevEmployeequalification->getStatus()) {
                                            // Set End Date                                        
                                            $history->setDateTo(new \DateTime());
                                        }
                                    }
                                    // Set new status
                                    $prevEmployeequalification->setStatus($status);
                                }
                                // Add new history
                                $newHistory = new \Pmwebdesign\Staffm\Domain\Model\History();
                                $newHistory->setStatus($status);
                                $newHistory->setDateFrom(new \DateTime());

                                $newHistory->setAssessor($assessor);
                                $histories->attach($newHistory);
                                $prevEmployeequalification->setHistories($histories);
                            }
                        }
                        if ($note != null) {
                            $prevEmployeequalification->setNote($note);
                        }
                        break;
                    }
                }

                // Previous employeequalification found?   
                if ($prevStatus == FALSE) {
                    // No, set new employeequalification
                    $employeequalification->setEmployee($employee);
                    $employeequalification->setQualification($qualification);
                    // Status?
                    if ($status != null) {
                        $employeequalification->setStatus($status);
                    }
                    // Notice?
                    if ($note != null) {
                        $employeequalification->setNote($note);
                    }
                    // Reminder date?
                    if ($reminderDate != null) {
                        $employeequalification->setReminderDate($reminderDate);
                    }
                    // Add new history               
                    $newHistory = new \Pmwebdesign\Staffm\Domain\Model\History();
                    $newHistory->setStatus($status);
                    $newHistory->setDateFrom(new \DateTime());
                    $newHistory->setAssessor($assessor);
                    $histories->attach($newHistory);
                    $employeequalification->setHistories($histories);
                    $employeequalifications->attach($employeequalification);
                } else {
                    // Yes, update previous employeequalification                    
                    // Notice?
                    if ($note != null) {
                        $prevEmployeequalification->setNote($note);
                    } else {
                        $prevEmployeequalification->setNote("");
                    }
                    // Reminder date?
                    if ($reminderDate != null) {
                        $prevEmployeequalification->setReminderDate($reminderDate);
                    } else {
                        $prevEmployeequalification->setReminderDate(NULL);
                    }
                    $employeequalifications->attach($prevEmployeequalification);
                }
            }
            return $employeequalifications;
        }
    }
    
    /**
     * Check assigned qualification with status for employees
     * 
     * @param \TYPO3\CMS\Extbase\Mvc\Request $request
     * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager    
     * @return void
     */
    public function setEmployeequalificationsFromQualifications(\TYPO3\CMS\Extbase\Mvc\Request $request, \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager)
    {
        if ($request->hasArgument('mitarbeiters')) {
            // Read request into array
            $ma = $request->getArgument('mitarbeiters');

            // Get logged in user
            $userService = GeneralUtility::makeInstance(\Pmwebdesign\Staffm\Domain\Service\UserService::class);
            $assessor = $userService->getLoggedInUser();

            // Set qualifications to array items
            foreach ($ma as $m => $arrVals) {                   
                /* @var $employee \Pmwebdesign\Staffm\Domain\Model\Mitarbeiter */
                $employee = $objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\MitarbeiterRepository::class)->findByUid($m);
                
                // Read previous qualifications of employee  
                $prevEmployeequalifications = $employee->getEmployeequalifications();
                
                // Read aktually qualifications of employee
                $aktEmployeequalifications = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
                foreach ($arrVals as $qualificationuid => $statusuid) {                    
                    // Status 0?
                    if($statusuid != 0) {
                        $aktEmployeequalification = new \Pmwebdesign\Staffm\Domain\Model\Employeequalification();
                        $aktEmployeequalification->setEmployee($employee);
                        $qualification = $objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\QualifikationRepository::class)->findByUid($qualificationuid);
                        $aktEmployeequalification->setQualification($qualification);
                        $aktEmployeequalification->setStatus($statusuid);   
                        
                        if(($employee->getUid() == 2374 || $employee->getUid() == 2373) && $qualification->getUid() == 78) {
                            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($aktEmployeequalification);                            
                        }
                        
                        // Check if previous qualification exist
                        $histories = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
                        $prevStatus = FALSE;
                        foreach ($prevEmployeequalifications as $prevEmployeequalification) {                            
                            if ($prevEmployeequalification->getEmployee() === $employee && $prevEmployeequalification->getQualification() === $qualification) {                                
                                $prevStatus = TRUE;
                                // Employee exist, just update
                                if ($aktEmployeequalification->getStatus() != null) {
                                    $histories = $prevEmployeequalification->getHistories();
                                    // Status changed or no histories?
                                    if ($prevEmployeequalification->getStatus() <> $aktEmployeequalification->getStatus() || count($histories) <= 0) {
                                        // Status has changed?
                                        if ($prevEmployeequalification->getStatus() <> $aktEmployeequalification->getStatus()) {
                                            // Yes, status has changed   
                                            // Check history
                                            foreach ($histories as $history) {
                                                // Old status?
                                                if ($history->getStatus() == $prevEmployeequalification->getStatus()) {
                                                    // Set End Date                                        
                                                    $history->setDateTo(new \DateTime());
                                                }
                                            }
                                            // Set new status
                                            $prevEmployeequalification->setStatus($aktEmployeequalification->getStatus());
                                        }
                                        // Add new history
                                        $newHistory = new \Pmwebdesign\Staffm\Domain\Model\History();
                                        $newHistory->setStatus($aktEmployeequalification->getStatus());
                                        $newHistory->setDateFrom(new \DateTime());

                                        $newHistory->setAssessor($assessor);
                                        $histories->attach($newHistory);
                                        $prevEmployeequalification->setHistories($histories);
                                    }
                                }                                
                                break;
                            }
                        }          
                        
                        if(($employee->getUid() == 2374 || $employee->getUid() == 2373) && $qualification->getUid() == 78) {
//                            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($aktEmployeequalification);
                            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($prevStatus);
                        }
                        
                        // Previous employeequalification found?   
                        if ($prevStatus == FALSE) {                          
                            // No, set new employeequalification                            
                            // Add new history               
                            $newHistory = new \Pmwebdesign\Staffm\Domain\Model\History();
                            $newHistory->setStatus($aktEmployeequalification->getStatus());
                            $newHistory->setDateFrom(new \DateTime());
                            $newHistory->setAssessor($assessor);
                            $histories->attach($newHistory);
                            $aktEmployeequalification->setHistories($histories);
                            $aktEmployeequalifications->attach($aktEmployeequalification);                              
                        } else {
                            // Yes, update previous employeequalification                                    
                            $aktEmployeequalifications->attach($prevEmployeequalification);
                        }
                    }                     
                }
                $employee->setEmployeequalifications($aktEmployeequalifications);                
                $objectManager->get(\Pmwebdesign\Staffm\Domain\Repository\MitarbeiterRepository::class)->update($employee);      
            } 
            $objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class)->persistAll();
        }
    }

    /**
     * Check assigned qualifications for a category
     * 
     * @param \TYPO3\CMS\Extbase\Mvc\Request $request
     * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager  
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Pmwebdesign\Staffm\Domain\Model\Qualification>
     */
    public function getQualifications(\TYPO3\CMS\Extbase\Mvc\Request $request, \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager)
    {
        if ($request->hasArgument('qualifikationen')) {           
            $qualifications = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();

            // Read checkboxes into array
            $qua = $request->getArgument('qualifikationen');

            // Set qualifications to array items
            foreach ($qua as $q) {
                $qualification = new \Pmwebdesign\Staffm\Domain\Model\Qualifikation();
                $qualification = $objectManager->get(
                                'Pmwebdesign\\Staffm\\Domain\\Repository\\QualifikationRepository'
                        )->findByUid($q);

                $qualifications->attach($qualification);
            }
            return $qualifications;
        }
    }
}
