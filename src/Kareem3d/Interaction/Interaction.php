<?php namespace Kareem3d\Interaction;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Kareem3d\Eloquent\Model;
use Kareem3d\Membership\User;

class Interaction extends Model {

    const SEEN = 0;
    const CLICKED = 1;

    /**
     * @var string
     */
    protected $table = 'interactions';

    /**
     * @var string
     */
    protected $guarded = array('id');

    /**
     * @return mixed
     */
    public function saveOrUpdate()
    {
        $query = $this->where('user_id', $this->user_id)
            ->where('interactable_type', $this->interactable_type)
            ->where('interactable_id', $this->interactable_id);

        // Remember if it's clicked then it's seen
        if($query->count() > 0)
        {
            // Check if the type is clicked to update the type
            if($this->type == static::CLICKED)

                return $query->update(array('type' => static::CLICKED));
        }
        else
        {
            return $this->save();
        }

    }

    /**
     * @param User $user
     * @param Model $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function notSeen(User $user, Model $model)
    {
        $query = static::getByUserAndModelQuery(static::SEEN, $user, $model);

        $lastSeenModel = $query->orderBy('id', 'DESC')->take(1)->first(array('id'));

        // If last seen model is set then return the number of models inserted after
        // this last seen model (comparing by id not timestamps).
        if($lastSeenModel)
        {
            return $model->where('id', '>', $lastSeenModel->id)->get();
        }

        // If last seen model is not set then return all models.
        return $model->all();
    }

    /**
     * @param User $user
     * @param Model $model
     * @return \Kareem3d\Eloquent\Model
     */
    public static function seen(User $user, Model $model)
    {
        $interaction = new Interaction(array(
            'user_id' => $user->id,
            'interactable_type' => $model->getClass(),
            'interactable_id' => $model->id,
            'type' => static::SEEN
        ));

        return $interaction->saveOrUpdate();
    }

    /**
     * @param User $user
     * @param Model $model
     * @return bool
     */
    public static function hasSeen(User $user, Model $model)
    {
        return static::getByUserAndModelQuery(static::SEEN, $user, $model)->count() > 0;
    }

    /**
     * @param User $user
     * @return mixed
     */
    public static function getSeenBy(User $user)
    {
        return static::extractModel(static::getByUserQuery(static::SEEN, $user)->get());
    }

    /**
     * @param Model $model
     * @return Collection
     */
    public static function whoSaw(Model $model)
    {
        return static::extractUser(static::getByModelQuery(static::SEEN, $model)->get());
    }

    /**
     * @param User $user
     * @param Model $model
     * @return \Kareem3d\Eloquent\Model
     */
    public static function clicked(User $user, Model $model)
    {
        $interaction = new Interaction(array(
            'user_id' => $user->id,
            'interactable_type' => $model->getClass(),
            'interactable_id' => $model->id,
            'type' => static::CLICKED
        ));

        return $interaction->saveOrUpdate();
    }

    /**
     * @param User $user
     * @param Model $model
     * @return bool
     */
    public static function hasClicked(User $user, Model $model)
    {
        return static::getByUserAndModelQuery(static::CLICKED, $user, $model)->count() > 0;
    }

    /**
     * @param User $user
     * @return mixed
     */
    public static function getClickedBy(User $user)
    {
        return static::extractModel(static::getByUserQuery(static::CLICKED, $user)->get());
    }

    /**
     * @param Model $model
     * @return Collection
     */
    public static function whoClicked(Model $model)
    {
        return static::extractUser(static::getByModelQuery(static::CLICKED, $model)->get());
    }

    /**
     * @param \Illuminate\Support\Collection $collection
     * @return Collection
     */
    protected static function extractUser( Collection $collection )
    {
        return $collection->map(function( Interaction $interaction )
        {
            return $interaction->user;
        });
    }

    /**
     * @param \Illuminate\Support\Collection $collection
     * @return Collection
     */
    protected static function extractModel( Collection $collection )
    {
        return $collection->map(function( Interaction $interaction )
        {
            return $interaction->interactable;
        });
    }

    /**
     * @param $type
     * @param User $user
     * @return Builder
     */
    protected static function getByUserQuery( $type, User $user )
    {
        return static::getByType($type)->where('user_id', $user->id);
    }

    /**
     * @param $type
     * @param Model $model
     * @return Builder
     */
    protected static function getByModelQuery( $type , Model $model )
    {
        return static::getByType($type)->where('interactable_type', $model->getClass())->where('interactable_id', $model->id);
    }

    /**
     * @param $type
     * @param User $user
     * @param Model $model
     * @return Builder|static
     */
    protected static function getByUserAndModelQuery( $type, User $user, Model $model )
    {
        return static::getByType($type)
            ->where('user_id', $user->id)
            ->where('interactable_type', $model->getClass())
            ->where('interactable_id', $model->id);
    }

    /**
     * @param $type
     * @return Builder
     */
    protected static function getByType( $type )
    {
        return static::where('type', '>=', $type);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        $user = App::make('Kareem3d\Membership\User');

        return $this->belongsTo($user->getClass());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function interactable()
    {
        return $this->morphTo();
    }
}