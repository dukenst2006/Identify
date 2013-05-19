<?php namespace Regulus\Identify;

use Illuminate\Database\Eloquent\Model as Eloquent;

use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableInterface;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\URL;

use Regulus\Identify\Identify as Auth;

class User extends Eloquent implements UserInterface, RemindableInterface {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'users';

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = array('password');

	/**
	 * The attributes that cannot be updated.
	 *
	 * @var array
	 */
	protected $guarded = array('id');

	/**
	 * The constructor which adds the table prefix from the config settings.
	 *
	 */
	public function __construct()
	{
		$this->table = Config::get('identify::tablePrefix').$this->table;
	}

	/**
	 * The role of the user.
	 *
	 * @var array
	 */
	public function roles()
	{
		return $this->belongsToMany('Regulus\Identify\Role', Config::get('identify::tablePrefix').'user_roles');
	}

	/**
	 * Allow user to be used in polymorphic relationships.
	 *
	 * @var array
	 */
	public function content()
	{
		return $this->morphTo();
	}

	/**
	 * Get the unique identifier for the user.
	 *
	 * @return mixed
	 */
	public function getAuthIdentifier()
	{
		return $this->getKey();
	}

	/**
	 * Get the password for the user.
	 *
	 * @return string
	 */
	public function getAuthPassword()
	{
		return $this->password;
	}

	/**
	 * Get the e-mail address where password reminders are sent.
	 *
	 * @return string
	 */
	public function getReminderEmail()
	{
		return $this->email;
	}

	/**
	 * Get the picture for the user.
	 *
	 * @param  boolean  $thumbnail
	 * @return string
	 */
	public function getPicture($thumbnail = false)
	{
		$picture = URL::asset('assets/img/display-pic-default.png');

		if ( ! $thumbnail) {
			$file = Config::get('identify::pathPicture').Config::get('identify::filenamePicture');
		} else {
			$file = Config::get('identify::pathPictureThumbnail').Config::get('identify::filenamePictureThumbnail');
		}
		$file = str_replace(':userID', $this->id, $file);

		if (is_file($file)) {
			$picture = URL::to($file);
		}
		return $picture;
	}

	/**
	 * Get the name of the user.
	 *
	 * @return string
	 */
	public function getName()
	{
		$name = $this->first_name;
		if ($this->last_name != "") {
			if ($name != "") $name .= " ";
			$name .= $this->last_name;
		}
		return $name;
	}

	/**
	 * Attempt to activate a user account by the user ID and activation code.
	 *
	 * @param  integer  $id
	 * @param  string   $activationCode
	 * @return boolean
	 */
	public static function activate($id = 0, $activationCode = '')
	{
		$user = User::find($id);
		if (!empty($user) && !$user->activated && (static::is('admin') || $activationCode == $user->activation_code)) {
			$user->active       = true;
			$user->activated_at = date('Y-m-d H:i:s');
			$user->save();
			return true;
		}
		return false;
	}

	/**
	 * Create a new user account.
	 *
	 * @return boolean
	 */
	public static function createAccount()
	{
		//check for role
		if (Auth::is('admin')) {
			$roleName = Input::get('role');
		} else {
			$roleName = "Member";
		}
		$role = Role::where('name', '=', $roleName)->first();
		if (empty($role)) return false;

		$user = new static;
		$user->updateAccount('create');

		//add user role
		$userRole = new UserRole;
		$userRole->user_id = $user->id;
		$userRole->role_id = $role->id;
		$userRole->save();

		return $user;
	}

	/**
	 * Update a user account.
	 *
	 * @return boolean
	 */
	public function updateAccount($types = 'standard')
	{
		$dataSetup = Config::get('identify::dataSetup');
		if (is_string($types)) $types = array($types);
		foreach ($types as $type) {
			if (isset($dataSetup[$type])) {
				foreach ($dataSetup[$type] as $field => $value) {
					$this->{$field} = $value;
				}
			}
		}

		$this->save();
		return true;
	}

}