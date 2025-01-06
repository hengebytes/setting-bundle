<?php

namespace Hengebytes\SettingBundle\Controller;

use Hengebytes\SettingBundle\Interfaces\SettingHandlerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};

class SettingController extends AbstractController
{
    public function __construct(protected SettingHandlerInterface $settingHandler)
    {
    }

    public function indexAction(Request $request): Response
    {
        $post = $request->request->all();
        $errors = [];
        $feedback = '';

        if (count($post) > 0) {
            if (isset($post['UpdateButton'])) {
                [$errors, $feedback] = $this->processUpdate($post, $errors);
            } elseif (isset($post['CreateButton'], $post['new'])) {
                [$errors, $feedback] = $this->processCreate($post, $errors);
            } elseif (isset($post['DeleteButton'])) {
                [$errors, $feedback] = $this->processRemoveSetting($post, $errors);
            }
        }

        $groupedSettings = $this->settingHandler->getGrouped();
        ksort($groupedSettings);

        return $this->render('@Setting/setting_layout.html.twig',
            [
                'action' => 'settings',
                'is_production_env' => $this->settingHandler->isProductionEnvironment(),
                'grouped_settings' => $groupedSettings,
                'errors' => $errors,
                'feedback' => $feedback,
            ]
        );
    }

    private function processUpdate(array $post, array $errors): array
    {
        $feedback = '';
        $allSettings = $post['settings'] ?? [];
        $selectedSettings = $post['selected_settings'] ?? [];
        if (count($selectedSettings) === 0) {
            $errors[] = 'not_selected.update';
        } else {
            foreach ($selectedSettings as $selectedSetting) {
                $updateSetting = $allSettings[$selectedSetting] ?? null;
                if ($updateSetting !== null) {
                    $this->settingHandler->set($updateSetting['name'], $updateSetting['value']);
                    if ($updateSetting['name'] !== $selectedSetting) {
                        $this->settingHandler->remove($selectedSetting);
                    }
                    $feedback = 'success.selected_update';
                }
            }
        }

        return [$errors, $feedback];
    }

    private function processCreate(array $post, array $errors): array
    {
        $newSettingData = $post['new'];
        $feedback = '';
        if (empty($newSettingData['name']) || !isset($newSettingData['value'])) {
            $errors[] = 'empty_name';
        }
        $settingExist = $this->settingHandler->get($newSettingData['name']) !== null;
        if ($settingExist) {
            $errors[] = 'name_exist';
        }
        if (count($errors) === 0) {
            $this->settingHandler->set($newSettingData['name'], $newSettingData['value']);
            $feedback = 'success.add';

            return [$errors, $feedback];
        }

        return [$errors, $feedback];
    }

    private function processRemoveSetting(array $post, array $errors): array
    {
        $feedback = '';
        $allSettings = $post['settings'] ?? [];
        $selectedSettings = $post['selected_settings'] ?? [];
        if (count($selectedSettings) === 0) {
            $errors[] = 'not_selected.remove';
        } else {
            foreach ($selectedSettings as $selectedSetting) {
                $removeSetting = $allSettings[$selectedSetting] ?? null;
                if ($removeSetting !== null) {
                    $this->settingHandler->remove($removeSetting['name']);
                    $feedback = 'success.selected_remove';
                }
            }
        }

        return [$errors, $feedback];
    }
}
