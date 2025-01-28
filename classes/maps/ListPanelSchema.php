<?php
/**
 * @file \plugins\generic\userComments\classes\maps\Schema.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2000-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Schema
 *
 * @brief Map highlights to the properties defined in the highlight schema
 */


namespace APP\plugins\generic\userComments\classes\maps; 

use PKP\services\PKPSchemaService;
use Illuminate\Support\Enumerable;
use APP\plugins\generic\userComments\classes\userComment\UserComment; 
use APP\plugins\generic\userComments\classes\facades\Repo;



class ListPanelSchema extends \PKP\core\maps\Schema
{
    public Enumerable $collection;
 
    public string $schema = 'userComment';

    public function map(UserComment $item): array
    {
        // Get the properties defined in the schema
        $props = $this->getProps();

        return $this->mapByProperties($props, $item);
    }

    /**
     * Map a collection of Decisions
     *
     * @see self::map
     */
    public function mapMany(Enumerable $collection): Enumerable
    {
        $this->collection = $collection;
        return $collection->map(function ($item) {
            return $this->map($item);
        });
    }


    protected function mapByProperties(array $props, UserComment $item): array
    {
        $output = [];
        foreach ($props as $prop) {

            $user = Repo::user()->get((int) $item->getUserId()); 
            $submission = Repo::submission()->get((int) $item->getSubmissionId()); 

            
            switch ($prop) {

                // Any property defined in the schema that isn't
                // available in the DataObject must be set manually.
                case 'userName':
                    $output[$prop] = $user->getFullName();
                    break;

                case 'userOrcid':
                    $output[$prop] = $user->getData('orcid');
                    break;

                case 'userAffiliation':
                    $output[$prop] = $user->getLocalizedAffiliation();
                    break;                    

                case 'commentId':
                    $output['id'] = $item->getData($prop);
                    break;  

                case 'submissionId':
                    $output['title'] = "Submission: " . $submission->getLocalizedTitle();
                    break;   

                case 'dateFlagged':                    
                    $output['subtitle'] = "flagged: " . $item->getData($prop);                    
                    break;                       

                // Get other properties from the DataObject
                default:
                    $output[$prop] = $item->getData($prop);
                    break;
            }
        }

        return $this->withExtensions($output, $item);
    }
}