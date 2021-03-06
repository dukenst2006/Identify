<?php namespace Regulus\Identify\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

use Auth;

class Permission extends Model {

	use SoftDeletes;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'permissions';

	/**
	 * The attributes that cannot be updated.
	 *
	 * @var array
	 */
	protected $guarded = ['id'];

	/**
	 * Enable soft delete for the model.
	 *
	 * @var array
	 */
	protected $dates = ['deleted_at'];

	/**
	 * The constructor which adds the table prefix from the config settings.
	 *
	 */
	public function __construct(array $attributes = [])
	{
		parent::__construct($attributes);

		$this->table = Auth::getTableName($this->table);
	}

	/**
	 * The users that have the permission.
	 *
	 * @return Collection
	 */
	public function users()
	{
		return $this->belongsToMany(config('auth.model'), Auth::getTableName('user_permissions'))
			->orderBy('username');
	}

	/**
	 * The roles that have the permission.
	 *
	 * @return Collection
	 */
	public function roles()
	{
		return $this->belongsToMany('Regulus\Identify\Models\Role', Auth::getTableName('role_permissions'))
			->orderBy('name');
	}

	/**
	 * The parent permission that the permission belongs to.
	 *
	 * @return Permission
	 */
	public function parentPermission()
	{
		return $this->belongsTo('Regulus\Identify\Models\Permission', 'parent_id');
	}

	/**
	 * The sub permissions of the permission.
	 *
	 * @return Collection
	 */
	public function subPermissions()
	{
		return $this->hasMany('Regulus\Identify\Models\Permission', 'parent_id');
	}

	/**
	 * Get a select box list of permissions.
	 *
	 * @param  mixed    $select
	 * @return array
	 */
	public static function getSelectable($select = null)
	{
		if (is_null($select) || !is_array($select) || count($select) == 0)
			$select = ['id', 'name'];

		if (count($select) == 1)
			$select[1] = $select[0];

		$permissions = static::orderBy('name')->get();
		$options     = [];

		foreach ($permissions as $permission) {
			$options[$permission->{$select[0]}] = $permission->{$select[1]};
		}

		return $options;
	}

}