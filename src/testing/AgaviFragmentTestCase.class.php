<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2005-2008 the Agavi Project.                                |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+

/**
 * AgaviFragmentTestCase is the base class for all fragment tests and provides
 * the necessary assertions
 * 
 * 
 * @package    agavi
 * @subpackage testing
 *
 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
 * @copyright  The Agavi Project
 *
 * @since      1.0.0
 *
 * @version    $Id$
 */
abstract class AgaviFragmentTestCase extends PHPUnit_Framework_TestCase implements AgaviIFragmentTestCase
{
	
	/**
	 * @var        string the name of the context to use, null for default context
	 */
	protected $contextName = null;
	
	/**
	 * @var        string the name of the action to test
	 */
	protected $actionName;
	
	/**
	 * @var        string the name of the module 
	 */
	protected $moduleName;
	
	/**
	 * @var        string the name of the resulting view
	 */
	protected $viewName;
	
	/**
	 * @var        string the name of the resulting view's module
	 */
	protected $viewModuleName;
	
	/**
	 * @var        bool   the result of the validation process
	 */
	protected $validationSuccess;
	
	/**
	 * @var        AgaviExecutionContainer the container to run the action in
	 */
	protected $container;


	/**
	 * Constructs a test case with the given name.
	 *
	 * @param  string $name
	 * @param  array  $data
	 * @param  string $dataName
	 */
	public function __construct($name = NULL, array $data = array(), $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->setRunTestInSeparateProcess(true);
	}
	
	
	/**
	 * creates a new AgaviExecutionContainer for each test
	 * 
	 * @return void
	 * 
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	public function setUp()
	{
		$this->container = $this->createExecutionContainer();
	}
	
	
	/**
	 * unsets the AgaviExecutionContainer after each test
	 * 
	 * @return void
	 * 
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	public function tearDown()
	{
		$this->container = null;
	}
	
	/**
	 * retrieve the application context
	 * 
	 * @return     AgaviContext the application context
	 * 
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	public function getContext()
	{
		return AgaviContext::getInstance($this->contextName);
	}
	
	/**
	 * normalizes a viewname according to the configured rules
	 * 
	 * Please do not use this method, it exists only for internal 
	 * purposes and will be removed ASAP. You have been warned
	 * 
	 * @param      string the short view name
	 * 
	 * @return     string the full view name
	 * 
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	protected function normalizeViewName($shortName)
	{
		if($shortName != AgaviView::NONE) {
			$shortName = AgaviToolkit::expandVariables(
				AgaviToolkit::expandDirectives(
					AgaviConfig::get(
						sprintf('modules.%s.agavi.view.name', strtolower($this->moduleName)),
						'${actionName}${viewName}'
					)
				),
				array(
					'actionName' => $this->actionName,
					'viewName' => $shortName,
				)	
			);	
		}
		
		return $shortName;
	}

	/**
	 * create an executionfilter for the test
	 * 
	 * the configured executionfilter class will be wrapped in a testing
	 * extension to provide advanced capabilities required for testing 
	 * only
	 * 
	 * @return     AgaviExecutionFilter 
	 * 
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	protected function createExecutionFilter()
	{
		$effi = $this->getContext()->getFactoryInfo('execution_filter');

		$wrapper_class = $effi['class'].'UnitTesting';

		//extend the original class to overwrite runAction, so that the containers request data is cloned
		if(!class_exists($wrapper_class)) {
			$code = sprintf('
class %1$s extends %2$s
{
	protected $validationResult = null;

	public function performValidation(AgaviExecutionContainer $container)
	{	
		return  parent::performValidation($container);
	}

	public function runAction(AgaviExecutionContainer $container)
	{
		$container->cloneArgumentsToRequestData();
		return parent::runAction($container);
	}
}',
			$wrapper_class,
			$effi['class']);

			eval($code);
		}

		// create a new execution container with the wrapped class
		$filter = new $wrapper_class();
		$filter->initialize($this->getContext(), $effi['parameters']);
		return $filter;
	}

	/**
	 * create an AgaviExecutionContainer for the test
	 * 
	 * the configured AgaviExecutionContainer class will be wrapped in a testing
	 * extension to provide advanced capabilities required for testing 
	 * only
	 * 
	 * @return     AgaviExecutionContainer 
	 * 
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	protected function createExecutionContainer()
	{
		$context = $this->getContext();

		$ecfi = $context->getFactoryInfo('execution_container');
		$wrapper_class = $ecfi['class'].'UnitTesting';

		//extend the original class to add a setter for the action instance
		if(!class_exists($wrapper_class)) {
			$code = sprintf('
class %1$s extends %2$s
{
	protected $validationResult = null;

	public function performValidation()
	{	
		if(null === $this->validationResult) {
			$this->cloneArgumentsToRequestData();
			$this->validationResult = parent::performValidation();
		}
		return $this->validationResult;
	}

	public function cloneArgumentsToRequestData()
	{
		$this->requestData = clone $this->arguments;
	}

	public function setActionInstance(AgaviAction $action)
	{
		$this->actionInstance = $action;
	}
}',
			$wrapper_class,
			$ecfi['class']);

			eval($code);
		}

		// create a new execution container with the wrapped class
		$container = new $wrapper_class();
		$container->initialize($context, $ecfi['parameters']);
		$container->setModuleName($this->moduleName);
		$container->setActionName($this->actionName);
		$container->setArguments($this->createRequestDataHolder(array()));

		return $container;
	}

	/* --- container delegates --- */

	/**
	 * @see        AgaviExcutionContainer::setOutputType()
	 *
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	protected function setOutputType(AgaviOutputType $outputType)
	{
		$this->container->setOutputType($outputType);
	}

	/**
	 * @see        AgaviExcutionContainer::setArguments()
	 *
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	protected function setArguments(AgaviRequestDataHolder $rd)
	{
		$this->container->setArguments($rd);
	}

	/**
	 * @see        AgaviExcutionContainer::setRequestMethod()
	 *
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	protected function setRequestMethod($method)
	{
		$this->container->setRequestMethod($method);
	}

	/**
	 * @see        AgaviAttributeHolder::clearAttributes()
	 *
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	protected function clearAttributes()
	{
		$this->container->clearAttributes();
	}

	/**
	 * @see        AgaviAttributeHolder::getAttribute()
	 *
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	protected function &getAttribute($name, $default = null)
	{
		return $this->container->getAttribute($name, null, $default);
	}

	/**
	 * @see        AgaviAttributeHolder::getAttributeNames()
	 *
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	protected function getAttributeNames()
	{
		return $this->container->getAttributeNames();
	}

	/**
	 * @see        AgaviAttributeHolder::getAttributes()
	 *
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	protected function &getAttributes()
	{
		return $this->container->getAttributes();
	}

	/**
	 * @see        AgaviAttributeHolder::hasAttribute()
	 *
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	protected function hasAttribute($name)
	{
		return $this->container->hasAttribute($name);
	}

	/**
	 * @see        AgaviAttributeHolder::removeAttribute()
	 *
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	protected function &removeAttribute($name)
	{
		return $this->container->removeAttribute($name);
	}

	/**
	 * @see        AgaviAttributeHolder::setAttribute()
	 *
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	protected function setAttribute($name, $value)
	{
		$this->container->setAttribute($name, $value);
	}

	/**
	 * @see        AgaviAttributeHolder::appendAttribute()
	 *
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	protected function appendAttribute($name, $value)
	{
		$this->container->appendAttribute($name, $value);
	}

	/**
	 * @see        AgaviAttributeHolder::setAttributesByRef()
	 *
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	protected function setAttributeByRef($name, &$value)
	{
		$this->container->setAttributeByRef($name, $value);
	}

	/**
	 * @see        AgaviAttributeHolder::appendAttributeByRef()
	 *
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	protected function appendAttributeByRef($name, &$value)
	{
		$this->container->appendAttributeByRef($name, $value);
	}

	/**
	 * @see        AgaviAttributeHolder::setAttributes()
	 *
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	protected function setAttributes(array $attributes)
	{
		$this->container->setAttributes($attributes);
	}

	/**
	 * @see        AgaviAttributeHolder::setAttributesByRef()
	 *
	 * @author     Felix Gilcher <felix.gilcher@bitextender.com>
	 * @since      1.0.0
	 */
	protected function setAttributesByRef(array &$attributes)
	{
		$this->container->setAttributesByRef($attributes);
	}
}

?>