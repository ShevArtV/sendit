<?php

include_once __DIR__ . '/cabinet.class.php';
include_once __DIR__ . '/orderfields.class.php';

/**
 * Сохранённые адреса доставки покупателя (siUserAddress) и их подстановка в оформление заказа.
 */
class siUserAddresses extends siCabinet
{
    protected const METHODS = ['save' => 'addresses', 'remove' => 'addresses', 'setDefault' => 'addresses', 'apply' => 'addresses'];

    /**
     * @param \modX $modx
     * @return void
     */
    public static function addPackage(\modX $modx): void
    {
        if (!isset($modx->packages['sendit'])) {
            $modx->addPackage('sendit', $modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/sendit/model/');
        }
    }

    /**
     * @param \modX $modx
     * @param int $userId
     * @return array адреса покупателя, поля адреса развёрнуты из data
     */
    public static function getList(\modX $modx, int $userId): array
    {
        self::addPackage($modx);
        $c = $modx->newQuery('siUserAddress', ['user_id' => $userId]);
        $c->sortby('is_default', 'DESC');
        $c->sortby('id', 'ASC');
        $list = [];
        foreach ($modx->getIterator('siUserAddress', $c) as $address) {
            $list[] = self::toArray($address);
        }
        return $list;
    }

    /**
     * @param \modX $modx
     * @param int $userId
     * @return array поля адреса по умолчанию
     */
    public static function getDefaultFields(\modX $modx, int $userId): array
    {
        if (!$userId) {
            return [];
        }
        self::addPackage($modx);
        $address = $modx->getObject('siUserAddress', ['user_id' => $userId, 'is_default' => 1]);
        return $address ? self::getFields($address) : [];
    }

    /**
     * @param siUserAddress $address
     * @return array
     */
    public static function getFields($address): array
    {
        $data = $address->get('data');
        $data = is_array($data) ? $data : (json_decode((string)$data, true) ?: []);
        return array_filter($data, static function ($value) {
            return $value !== '' && $value !== null;
        });
    }

    /**
     * @param siUserAddress $address
     * @return array
     */
    public static function toArray($address): array
    {
        $fields = self::getFields($address);
        return array_merge($fields, [
            'id' => (int)$address->get('id'),
            'name' => $address->get('name'),
            'is_default' => (bool)$address->get('is_default'),
            'fields' => $fields,
        ]);
    }

    /**
     * Адрес текущего покупателя из POST address_id.
     * @return siUserAddress|null
     */
    protected function getAddress()
    {
        self::addPackage($this->modx);
        $id = (int)($_POST['address_id'] ?? 0);
        if (!$id) {
            return null;
        }
        return $this->modx->getObject('siUserAddress', ['id' => $id, 'user_id' => $this->userId]);
    }

    /**
     * Создание или правка адреса. Поля адреса — ключи полей заказа miniShop2 (siOrderFields::ADDRESS_FIELDS).
     * @return array
     */
    public function save(): array
    {
        $address = $this->getAddress();
        if (!empty($_POST['address_id']) && !$address) {
            return $this->SendIt->error('si_msg_cabinet_address_nf');
        }
        $fields = [];
        foreach (siOrderFields::ADDRESS_FIELDS as $key) {
            if (isset($_POST[$key]) && !is_array($_POST[$key])) {
                $fields[$key] = trim((string)$_POST[$key]);
            }
        }
        $isNew = !$address;
        [$error, $data] = $this->before('saveAddress', [
            'name' => trim((string)($_POST['name'] ?? '')),
            'fields' => $fields,
            'is_default' => !empty($_POST['is_default']) || ($isNew && !self::getList($this->modx, $this->userId)),
        ], $address);
        if ($error) {
            return $this->SendIt->error($error);
        }
        if ($isNew) {
            $address = $this->modx->newObject('siUserAddress', [
                'user_id' => $this->userId,
                'createdon' => time(),
            ]);
        }
        $address->fromArray([
            'name' => $data['name'] ?: implode(', ', array_filter([$data['fields']['city'] ?? '', $data['fields']['street'] ?? ''])),
            'data' => json_encode($data['fields'], JSON_UNESCAPED_UNICODE),
            'updatedon' => time(),
        ]);
        if (!$address->save()) {
            return $this->SendIt->error('si_msg_cabinet_address_save_err');
        }
        if ($data['is_default']) {
            $this->markDefault($address);
        }
        $this->after('saveAddress', $data, $address);

        return $this->success('si_msg_cabinet_address_saved', ['address' => self::toArray($address)]);
    }

    /**
     * @return array
     */
    public function remove(): array
    {
        if (!$address = $this->getAddress()) {
            return $this->SendIt->error('si_msg_cabinet_address_nf');
        }
        [$error, $data] = $this->before('removeAddress', [], $address);
        if ($error) {
            return $this->SendIt->error($error);
        }
        $wasDefault = (bool)$address->get('is_default');
        $address->remove();
        if ($wasDefault && $next = $this->modx->getObject('siUserAddress', ['user_id' => $this->userId])) {
            $this->markDefault($next);
        }
        $this->after('removeAddress', $data, $address);

        return $this->success('si_msg_cabinet_address_removed', ['address_id' => (int)$address->get('id')]);
    }

    /**
     * @return array
     */
    public function setDefault(): array
    {
        if (!$address = $this->getAddress()) {
            return $this->SendIt->error('si_msg_cabinet_address_nf');
        }
        $this->markDefault($address);
        $this->after('setDefaultAddress', [], $address);

        return $this->success('si_msg_cabinet_address_default', ['address_id' => (int)$address->get('id')]);
    }

    /**
     * Подстановка адреса в оформляемый заказ; принятые поля уходят в ответ (orderFields) для заполнения формы.
     * @return array
     */
    public function apply(): array
    {
        if (!$address = $this->getAddress()) {
            return $this->SendIt->error('si_msg_cabinet_address_nf');
        }
        [$error, $data] = $this->before('applyAddress', ['orderFields' => siOrderFields::fillAddress(self::getFields($address))], $address);
        if ($error) {
            return $this->SendIt->error($error);
        }
        $data['orderFields'] = siOrderFields::apply($this->modx, $this->ms2, $data['orderFields']);
        $this->after('applyAddress', $data, $address);

        return $this->success('si_msg_cabinet_address_applied', [
            'address_id' => (int)$address->get('id'),
            'orderFields' => $data['orderFields'],
        ]);
    }

    /**
     * @param siUserAddress $address
     * @return void
     */
    protected function markDefault($address): void
    {
        $table = $this->modx->getTableName('siUserAddress');
        $stmt = $this->modx->prepare("UPDATE {$table} SET is_default = 0 WHERE user_id = :user AND id != :id");
        $stmt->execute([':user' => $this->userId, ':id' => (int)$address->get('id')]);
        $address->set('is_default', 1);
        $address->save();
    }
}
