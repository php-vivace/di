<?php
namespace vivace\di\Factory;

use vivace\di\BadDefinitionError;
use vivace\di\Factory;
use vivace\di\ImportFailureError;
use vivace\di\Resolver;
use vivace\di\Scope;


/**
 * Factory for instantiate object with automated resolving of dependencies
 * For resolving used instance of vivace\di\Resolver, which should be exported in your main scope
 * @package vivace\di
 */
class Instance implements Factory
{
    /** @var bool */
    private $service = false;
    /** @var */
    private $instance;
    /** @var array */
    private $arguments = [];
    /** @var callable */
    private $setUp;
    /** @var string */
    private $className;

    /**
     * Factory constructor.
     * @param string $className Target class name
     * @param array $arguments Parameters for resolver
     * @param bool $asService
     * @throws BadDefinitionError
     * @see Instance::setArguments
     */
    public function __construct(string $className, array $arguments = [], $asService = true)
    {
        $this->className = $className;
        $this->setArguments($arguments);
        $this->asService($asService);
    }

    /**
     * Set dependencies arguments for instantiate of target class
     * @param array $arguments Associative array, where key can be name, type or position of argument.
     * @return Factory|$this Instance of this object
     */
    public function setArguments(array $arguments): Factory
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * Make as a service. Instance will behave as a singleton pattern
     * @param bool $value enable or disable
     * @return Factory|$this Instance of target class
     */
    public function asService($value = true): Factory
    {
        $this->service = $value;
        return $this;
    }

    /**
     * Set function, which call will occur after instantiate
     * @param callable $function Takes two arguments, where first is a instance of target object, second is a instance of vivace\di\Scope
     * @return Factory|$this
     */
    public function setUp(callable $function): Factory
    {
        $this->setUp = $function;
        return $this;
    }

    /**
     * Instance object of target class
     * @param Scope $scope Uses scope for resolving dependencies
     * @return object Instance object of target class
     * @throws BadDefinitionError
     */
    public function produce(Scope $scope)
    {
        if ($this->service && $this->instance) {
            return $this->instance;
        }
        if (!class_exists($this->getClassName())) {
            throw new BadDefinitionError("Class {$this->getClassName()} not found");
        }
        /** @var Resolver $resolver */
        $resolver = $this->getResolver($scope);
        $arguments = $resolver->resolve($this->className, $this->arguments);
        $object = new $this->className(...$arguments);
        if (!empty($this->setUp)) {
            call_user_func($this->setUp, $object, $scope);
        }
        if ($this->service) {
            $this->instance = $object;
        }
        return $object;
    }

    protected function getResolver(Scope $scope): Resolver
    {
        return $scope->import(Resolver::class);
    }
    /**
     * @param Scope $scope
     * @return mixed
     * @throws ImportFailureError
     * @see Instance::produce
     */
    final public function __invoke(Scope $scope)
    {
        return $this->produce($scope);
    }

    /**
     * @return string return target class name
     */
    public function getClassName(): string
    {
        return $this->className;
    }
}
