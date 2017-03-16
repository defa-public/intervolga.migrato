<?namespace Intervolga\Migrato\Data\Module\Iblock;

use Bitrix\Main\Loader;
use Intervolga\Migrato\Data\BaseData;
use Intervolga\Migrato\Data\Record;
use Intervolga\Migrato\Data\RecordId;
use Intervolga\Migrato\Tool\XmlIdProvider\TypeXmlIdProvider;
use Bitrix\Main\Localization\LanguageTable;

class Type extends BaseData
{
	protected function __construct()
	{
		Loader::includeModule("iblock");
		$this->xmlIdProvider = new TypeXmlIdProvider($this);
	}

	public function getList(array $filter = array())
	{
		$result = array();
		$getList = \CIBlockType::GetList();
		while ($type = $getList->fetch())
		{
			$record = new Record($this);
			$id = RecordId::createStringId($type["ID"]);
			$record->setXmlId($this->getXmlId($id));
			$record->setId($id);
			$record->addFieldsRaw(array(
				"ID" => $type["ID"],
				"SECTIONS" => $type["SECTIONS"],
				"EDIT_FILE_BEFORE" => $type["EDIT_FILE_BEFORE"],
				"EDIT_FILE_AFTER" => $type["EDIT_FILE_AFTER"],
				"IN_RSS" => $type["IN_RSS"],
				"SORT" => $type["SORT"],
			));
			$result[] = $record;
		}

		return $result;
	}

	protected function getDefaultLanguages()
    {
        $languages = array();
        $rsLanguages = LanguageTable::getList(array(
            "select" => array(
                "LID"
            )
        ));
        while ($language = $rsLanguages->fetch())
        {
            $languages[$language["LID"]] = array("NAME" => "Unknown");
        }
        return $languages;
    }

	public function update(Record $record)
	{
		$typeObject = new \CIBlockType();
		$isUpdated = $typeObject->update($record->getId()->getValue(), $record->getFieldsRaw());
		if (!$isUpdated)
		{
			throw new \Exception(trim(strip_tags($typeObject->LAST_ERROR)));
		}
	}

	public function create(Record $record)
	{
		$fields = $record->getFieldsRaw();
		$fields["LANG"] = $this->getDefaultLanguages();
		$typeObject = new \CIBlockType();
		$typeId = $typeObject->add($fields);
		if ($typeId)
		{
			return $this->createId($typeId);
		}
		else
		{
			throw new \Exception(trim(strip_tags($typeObject->LAST_ERROR)));
		}
	}

	public function delete($xmlId)
	{
		$id = $this->findRecord($xmlId);
		$typeObject = new \CIBlockType();
		if (!$typeObject->delete($id))
		{
			throw new \Exception("Unknown error");
		}
	}
}