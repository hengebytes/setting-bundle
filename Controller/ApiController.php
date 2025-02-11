<?php

namespace Hengebytes\SettingBundle\Controller;

use Hengebytes\SettingBundle\Interfaces\SettingHandlerInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/settings', 'hengebytes_settings_api_')]
readonly class ApiController
{
    public function __construct(private SettingHandlerInterface $settingHandler)
    {
    }

    #[Route('/list', name: 'get_list', methods: ['GET'])]
    public function getGroupedSettings(): JsonResponse
    {
        $result = [];
        $groupedSettings = $this->settingHandler->getGrouped();
        ksort($groupedSettings);

        foreach ($groupedSettings as $group => $groupedSetting) {
            $result[$group] = array_values($groupedSetting);
        }

        return new JsonResponse($result);
    }

    #[Route('/list', 'update_list', methods: ['POST'])]
    public function bulkUpdateAction(Request $request): JsonResponse
    {
        try {
            $data = $request->toArray();
        } catch (JsonException $e) {
            $data = $request->request->all();
        }
        $settings = $data['settings'] ?? [];

        if (count($settings) === 0) {
            return new JsonResponse(['message' => 'Select setting to update'], Response::HTTP_BAD_REQUEST);
        }

        foreach ($settings as $setting) {
            $name = $setting['name'] ?? null;
            if (!$name) {
                continue;
            }
            $value = $setting['value'] ?? '';
            $isSensitive = $setting['is_sensitive'] ?? false;
            $this->settingHandler->set($name, $value, $isSensitive);
        }

        return new JsonResponse(['message' => 'Success']);
    }

    #[Route('/', name: 'create', methods: ['POST'])]
    public function setSetting(Request $request): JsonResponse
    {
        try {
            $data = $request->toArray();
        } catch (JsonException $e) {
            $data = $request->request->all();
        }

        $name = $data['name'] ?? null;
        $value = $data['value'] ?? '';
        $isSensitive = $data['is_sensitive'] ?? false;

        if (!$name) {
            return new JsonResponse(
                ['message' => 'Required parameter "name" is missed'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $settingExist = $this->settingHandler->get($name) !== null;
        if ($settingExist) {
            return new JsonResponse(['message' => 'Setting already exists.'], Response::HTTP_CONFLICT);
        }

        $this->settingHandler->set($name, $value, $isSensitive);

        return new JsonResponse(['success' => true]);
    }

    #[Route('/list', 'delete_list', methods: ['DELETE'])]
    public function bulkRemoveAction(Request $request): JsonResponse
    {
        try {
            $data = $request->toArray();
        } catch (JsonException) {
            $data = $request->request->all();
        }
        $settingNames = $data['settings'] ?? [];

        if (count($settingNames) === 0) {
            return new JsonResponse(['message' => 'Select setting to remove'], Response::HTTP_BAD_REQUEST);
        }

        foreach ($settingNames as $settingName) {
            $this->settingHandler->remove($settingName);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
