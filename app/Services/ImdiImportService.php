<?php

namespace App\Services;

use App\Models\Dialog;
use App\Models\Participant;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class ImdiImportService
{
    public function parse($filePath)
    {
        try {
            $xml = new SimpleXMLElement(file_get_contents($filePath));
            $session = $xml->Session;

            $data = [
                'dialog' => $this->parseSession($session),
                'participants' => $this->parseActors($session->MDGroup->Actors)
            ];

            return $data;
        } catch (\Exception $e) {
            Log::error("Error parsing IMDI file: " . $e->getMessage());
            throw $e;
        }
    }

    private function parseSession($session)
    {
        $mdGroup = $session->MDGroup;
        $location = $mdGroup->Location;
        $content = $mdGroup->Content;

        $dialogData = [
            'title' => (string)$session->Title,
            'date' => (string)$session->Date,
            'description' => (string)$session->Description,
            'continent' => (string)$location->Continent,
            'country' => (string)$location->Country,
            'region' => (string)$location->Region,
            'city' => (string)$location->Address, // In the example Address contains the city
            'genre' => (string)$content->Genre,
            'subgenre' => (string)$content->SubGenre,
        ];

        // Parse CommunicationContext
        if (isset($content->CommunicationContext)) {
            $ctx = $content->CommunicationContext;
            $dialogData['planning_type'] = (string)$ctx->PlanningType;
            $dialogData['researcher_involvement'] = (string)$ctx->Involvement;
            $dialogData['social_context'] = (string)$ctx->SocialContext;
        }

        // Parse Languages
        $subjectLanguages = [];
        $workingLanguages = [];
        if (isset($content->Languages)) {
            foreach ($content->Languages->Language as $lang) {
                $desc = (string)$lang->Description;
                $name = (string)$lang->Name;
                if (stripos($desc, 'Subject') !== false) {
                    $subjectLanguages[] = $name;
                }
                if (stripos($desc, 'Working') !== false) {
                    $workingLanguages[] = $name;
                }
            }
        }
        $dialogData['subject_languages'] = implode(', ', $subjectLanguages);
        $dialogData['working_languages'] = implode(', ', $workingLanguages);

        // Parse Keys
        if (isset($content->Keys)) {
            foreach ($content->Keys->Key as $key) {
                $name = (string)$key['Name'];
                $value = (string)$key;

                switch (strtolower($name)) {
                    case 'topic':
                        $dialogData['topic'] = $value;
                        break;
                    case 'restaurant_title':
                        $dialogData['restaurant_title'] = $value;
                        break;
                    case 'restaurant_features':
                        $dialogData['restaurant_features'] = $value;
                        break;
                    case 'customer_type':
                        $dialogData['customer_type'] = $value;
                        break;
                    case 'customer_profile':
                        $dialogData['customer_profile'] = $value;
                        break;
                    case 'customer_n':
                        $dialogData['customer_n'] = (int)$value;
                        break;
                    case 'speaking_customer_n':
                        $dialogData['speaking_customer_n'] = (int)$value;
                        break;
                    case 'menu_type':
                        $dialogData['menu_type'] = $value;
                        break;
                    case 'meal':
                        $dialogData['meal'] = $value;
                        break;
                    case 'speakers_features':
                    case 'spearkers_features': // Typo in example file
                        $dialogData['speakers_features'] = $value;
                        break;
                }
            }
        }

        return array_filter($dialogData);
    }

    private function parseActors($actors)
    {
        $participants = [];
        if (!$actors) return $participants;

        foreach ($actors->Actor as $actor) {
            $pData = [
                'full_name' => (string)$actor->FullName,
                'nickname' => (string)$actor->Name,
                'code' => (string)$actor->Code,
                'gender' => (string)$actor->Sex,
                'education' => (string)$actor->Education,
                'description' => (string)$actor->Description,
                'birth_year' => (string)$actor->BirthDate !== 'Unspecified' ? (string)$actor->BirthDate : null,
            ];

            // Languages
            $langs = [];
            if (isset($actor->Languages)) {
                foreach ($actor->Languages->Language as $lang) {
                    $langs[] = (string)$lang->Name;
                }
            }
            $pData['languages'] = implode(', ', $langs);

            // Keys
            if (isset($actor->Keys)) {
                foreach ($actor->Keys->Key as $key) {
                    $name = (string)$key['Name'];
                    $value = (string)$key;

                    switch (strtolower($name)) {
                        case 'nickname':
                            if (empty($pData['nickname'])) $pData['nickname'] = $value;
                            break;
                        case 'age_range':
                            $pData['age_range'] = $value;
                            break;
                        case 'role':
                            $pData['role'] = $value;
                            break;
                        case 'speaking_language':
                            $pData['speaker_language'] = $value;
                            break;
                        case 'primaryoccupation':
                            $pData['occupation'] = $value;
                            break;
                    }
                }
            }

            $participants[] = array_filter($pData);
        }

        return $participants;
    }

    public function importParticipants(Dialog $dialog, array $participantsData)
    {
        foreach ($participantsData as $pData) {
            $dialog->participants()->create($pData);
        }
    }
}
