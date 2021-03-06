<?php

/*
 * Copyright (C) 2019 pm-webdesign.eu 
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

namespace Pmwebdesign\Staffm\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Checks if the transfered user has a cost center responsible
 *
 * @author Markus Puffer <m.puffer@pm-webdesign.eu>
 */
class IsCostCenterResponsibleViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('employee', \Pmwebdesign\Staffm\Domain\Model\Mitarbeiter::class, '', true, null);
        $this->registerArgument('withDeputy', 'int', '', true, 0);
    }
    
    /**     
     * @return int
     */
    public function render()
    {
        // Receive cost centers for which the employee is responsible  
        $employee = $this->arguments['employee'];
        $withDeputy = $this->arguments['withDeputy'];
        if($employee != NULL ) {
            $objectManager =  GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);

            // Without Deputy?
            if($withDeputy == 0) {
                // Yes, just cost center responsible
                $costCenters = $objectManager->get('Pmwebdesign\\Staffm\\Domain\\Repository\\KostenstelleRepository')->findCostCentersFromResponsible($employee);    
            } elseif ($withDeputy > 0) {
                // No, with deputy of cost center responsible
                $costCenters = $objectManager->get('Pmwebdesign\\Staffm\\Domain\\Repository\\KostenstelleRepository')->findCostCentersFromResponsibleAndDeputy($employee);
            }
            // Cost centers available?   
            if(count($costCenters) > 0) {
                return count($costCenters);
            } 
        }
        return 0;
    }
}
