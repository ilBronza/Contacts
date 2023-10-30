<?php

namespace IlBronza\Contacts\Models;

use IlBronza\CRUD\Models\BaseModel;
use IlBronza\CRUD\Traits\Model\PackagedModelsTrait;

class ContactPackageBaseModel extends BaseModel
{
	use PackagedModelsTrait {
		// PackagedModelsTrait::getRouteBaseNamePrefix insteadof CRUDModelTrait;
	}

    
}