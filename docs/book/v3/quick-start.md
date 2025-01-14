# Quick Start

Forms are composed of elements and fieldsets. At the bare minimum, each element
or fieldset requires a name; in most situations, you'll also provide some
attributes to hint to the view layer how it might render the item. The form
itself generally composes an `InputFilter` &mdash; which you can also create
directly in the form via a factory. Individual elements can hint as to what
defaults to use when generating a related input for the input filter.

Perform form validation by providing an array of data to the `setData()` method,
and calling the `isValid()` method. If you want to simplify your work even more,
you can bind an object to the form; on successful validation, it will be
populated from the validated values.

## Creating Forms

Before a form can be used to process and validate data, it must first be created.
There are different alternativs of specifying and creation forms supported by this
component. Choosing the best approach depends on the individual needs in your project.

### Programmatic Form Creation

While forms can be created manually, this approach comes at the expense of
verbosity. However, this approach may be usable if you are using laminas-form as
a standalone component. Please see the page on
[programmation form creation](form-creation/programmatic-form-creation.md)
for examples.

### Creation via Factory

Creating forms via a factory provides a zero-code approach, where the form is
simply a matter of configuration. For examples, please see the page on
[form creation via factory](form-creation/creation-via-factory.md).

### Factory-backed Form Extension

Another alternative is implementing each form as its own class extending from
`\Laminas\Form\Form`. In contrast to the creation via factory this bears the advantage
that you can override certain functionality programmatically, if needed, while
elements can still be added using the same configuration approach. See the page on
[factory-backed form extension](form-creation/factory-backed-form-extension.md)
for further details and examples.

### Using Annotations or PHP8 Attributes

Lastly, forms can be created by using annotations to your models. This enables
maintaining models and their respective forms in a single file. Similar to the
creation via factory this is a zero-code approach. For further information on
additional installation requirements, the syntax of the supported annotations and
examples see the page on [annotation usage](form-creation/attributes-or-annotations.md).

## Validating Forms

Validating forms requires three steps. First, the form must have an input filter
attached. Second, you must inject the data to validate into the form. Third, you
validate the form. If invalid, you can retrieve the error messages, if any.

```php
// assuming $captcha is an instance of some Laminas\Captcha\AdapterInterface:
$form = new Contact\ContactForm($captcha);

// If the form doesn't define an input filter by default, inject one.
$form->setInputFilter(new Contact\ContactFilter());

// Get the data. In an MVC application, you might try:
$data = $request->getPost();  // for POST data
$data = $request->getQuery(); // for GET (or query string) data

$form->setData($data);

// Validate the form
if ($form->isValid()) {
    $validatedData = $form->getData();
} else {
    $messages = $form->getMessages();
}
```

> ### Always populate select elements with options
>
> Always ensure that options for a select element are populated *prior* to
> validation; otherwise, the element will fail validation, and you will receive
> a `NotInArray` error message.
>
> If you are populating the options from a database or other data source, make
> sure this is done prior to validation. Alternately, you may disable the
> `InArray` validator programmatically prior to validation:
>
> ```php
> $element->setDisableInArrayValidator(true);
> ```

You can get the raw data if you want, by accessing the composed input filter.

```php
$filter = $form->getInputFilter();

$rawValues    = $filter->getRawValues();
$nameRawValue = $filter->getRawValue('name');
```

## Hinting to the Input Filter

Often, you'll create elements that you expect to behave in the same way on each
usage, and for which you'll want specific filters or validation as well. Since
the input filter is a separate object, how can you achieve these latter points?

Because the default form implementation composes a factory, and the default
factory composes an input filter factory, you can have your elements and/or
fieldsets hint to the input filter. If no input or input filter is provided in
the input filter for that element, these hints will be retrieved and used to
create them.

To do so, one of the following must occur. For elements, they must implement
`Laminas\InputFilter\InputProviderInterface`, which defines a
`getInputSpecification()` method; for fieldsets (and, by extension, forms), they
must implement `Laminas\InputFilter\InputFilterProviderInterface`, which defines a
`getInputFilterSpecification()` method.

In the case of an element, the `getInputSpecification()` method should return
data to be used by the input filter factory to create an input. Every HTML5
(`email`, `url`, `color`, etc.) element has a built-in element that uses this
logic. For instance, here is how the `Laminas\Form\Element\Color` element is
defined:

```php
namespace Laminas\Form\Element;

use Laminas\Filter;
use Laminas\Form\Element;
use Laminas\InputFilter\InputProviderInterface;
use Laminas\Validator\Regex as RegexValidator;
use Laminas\Validator\ValidatorInterface;

class Color extends Element implements InputProviderInterface
{
    /**
     * Seed attributes
     *
     * @var array
     */
    protected $attributes = [
        'type' => 'color',
    ];

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * Get validator
     *
     * @return ValidatorInterface
     */
    protected function getValidator()
    {
        if (null === $this->validator) {
            $this->validator = new RegexValidator('/^#[0-9a-fA-F]{6}$/');
        }
        return $this->validator;
    }

    /**
     * Provide default input rules for this element
     *
     * Attaches an email validator.
     *
     * @return array
     */
    public function getInputSpecification()
    {
        return [
            'name' => $this->getName(),
            'required' => true,
            'filters' => [
                ['name' => Filter\StringTrim::class],
                ['name' => Filter\StringToLower::class],
            ],
            'validators' => [
                $this->getValidator(),
            ],
        ];
    }
}
```

The above hints to the input filter to create and attach an input named after
the element, marking it as required, giving it `StringTrim` and `StringToLower`
filters, and defining a `Regex` validator. Note that you can either rely on the
input filter to create filters and validators, or directly instantiate them.

For fieldsets, you do very similarly; the difference is that
`getInputFilterSpecification()` must return configuration for an input filter.

```php
namespace Contact\Form;

use Laminas\Filter;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator;

class SenderFieldset extends Fieldset implements InputFilterProviderInterface
{
    public function getInputFilterSpecification()
    {
        return [
            'name' => [
                'required' => true,
                'filters'  => [
                    ['name' => Filter\StringTrim::class],
                ],
                'validators' => [
                    [
                        'name' => Validator\StringLength::class,
                        'options' => [
                            'min' => 3,
                            'max' => 256
                        ],
                    ],
                ],
            ],
            'email' => [
                'required' => true,
                'filters'  => [
                    ['name' => Filter\StringTrim::class],
                ],
                'validators' => [
                    new Validator\EmailAddress(),
                ],
            ],
        ];
    }
}
```

Specifications are a great way to make forms, fieldsets, and elements re-usable
trivially in your applications. In fact, the `Captcha` and `Csrf` elements
define specifications in order to ensure they can work without additional user
configuration!

> ### Use the most specific input type
>
> If you set custom input filter specification either in
> `getInputSpecification()` or in `getInputFilterSpecification()`, the
> `Laminas\InputFilter\InputInterface` set for that specific field is reset to the
> default `Laminas\InputFilter\Input`.
>
> Some form elements may need a particular input filter, like
> `Laminas\Form\Element\File`: in this case it's mandatory to specify the `type`
> key in your custom specification to match the original one (e.g., for the
> file element, use `Laminas\InputFilter\FileInput`).

## Binding an object

As noted in the introduction, forms bridge the domain model and the view layer.
Let's see that in action.

When you `bind()` an object to the form, the following happens:

- The composed `Hydrator` calls `extract()` on the object, and uses the values
  returned, if any, to populate the `value` attributes of all elements. If a
  form contains a fieldset that itself contains another fieldset, the form will
  recursively extract the values.
- When `isValid()` is called, if `setData()` has not been previously set, the
  form uses the composed `Hydrator` to extract values from the object, and uses
  those during validation.
- If `isValid()` is successful (and the `bindOnValidate` flag is enabled, which
  is true by default), then the `Hydrator` will be passed the validated values
  to use to hydrate the bound object. (If you do not want this behavior, call
  `setBindOnValidate(FormInterface::BIND_MANUAL)`).
- If the object implements `Laminas\InputFilter\InputFilterAwareInterface`, the
  input filter it composes will be used instead of the one composed on the form.

This is easier to understand with an example.

```php
$contact = new ArrayObject;
$contact['subject'] = '[Contact Form] ';
$contact['message'] = 'Type your message here';

$form = new Contact\ContactForm;

$form->bind($contact); // form now has default values for
                       // 'subject' and 'message'

$data = [
    'name'    => 'John Doe',
    'email'   => 'j.doe@example.tld',
    'subject' => '[Contact Form] \'sup?',
];
$form->setData($data);

if ($form->isValid()) {
    // $contact now has the following structure:
    // [
    //     'name'    => 'John Doe',
    //     'email'   => 'j.doe@example.tld',
    //     'subject' => '[Contact Form] \'sup?',
    //     'message' => 'Type your message here',
    // ]
    // But is an ArrayObject instance!
}
```

When an object is bound to the form, calling `getData()` will return that object
by default. If you want to return an associative array instead, you can pass the
`FormInterface::VALUES_AS_ARRAY` flag to the method.

```php
use Laminas\Form\FormInterface;
$data = $form->getData(FormInterface::VALUES_AS_ARRAY);
```

Laminas ships several standard [hydrators](https://docs.laminas.dev/laminas-hydrator/);
you can create custom hydrators by implementing `Laminas\Hydrator\HydratorInterface`,
which looks like this:

``` sourceCode
namespace Laminas\Hydrator;

interface HydratorInterface
{
    /** @return array */
    public function extract($object);
    public function hydrate(array $data, $object);
}
```

## Rendering

As noted previously, forms are meant to bridge the domain model and view layer.
We've discussed the domain model binding, but what about the view?

The form component ships a set of form-specific view helpers. These accept the
various form objects, and introspect them in order to generate markup.
Typically, they will inspect the attributes, but in special cases, they may look
at other properties and composed objects.

When preparing to render, you will generally want to call `prepare()`. This
method ensures that certain injections are done, and ensures that elements
nested in fieldsets and collections generate names in array notation (e.g.,
`scoped[array][notation]`).

The base view helpers used everywhere are `Form`, `FormElement`, `FormLabel`,
and `FormElementErrors`. Let's use them to display the contact form.

```php
<?php
// within a view script
$form = $this->form;
$form->prepare();

// Assuming the "contact/process" route exists...
$form->setAttribute('action', $this->url('contact/process'));

// Set the method attribute for the form
$form->setAttribute('method', 'post');

// Get the form label plugin
$formLabel = $this->plugin('formLabel');

// Render the opening tag
echo $this->form()->openTag($form);
?>
<div class="form_element">
<?php
    $name = $form->get('name');
    echo $formLabel->openTag() . $name->getOption('label');
    echo $this->formInput($name);
    echo $this->formElementErrors($name);
    echo $formLabel->closeTag();
?></div>

<div class="form_element">
<?php
    $subject = $form->get('subject');
    echo $formLabel->openTag() . $subject->getOption('label');
    echo $this->formInput($subject);
    echo $this->formElementErrors($subject);
    echo $formLabel->closeTag();
?></div>

<div class="form_element">
<?php
    $message = $form->get('message');
    echo $formLabel->openTag() . $message->getOption('label');
    echo $this->formTextarea($message);
    echo $this->formElementErrors($message);
    echo $formLabel->closeTag();
?></div>

<div class="form_element">
<?php
    $captcha = $form->get('captcha');
    echo $formLabel->openTag() . $captcha->getOption('label');
    echo $this->formCaptcha($captcha);
    echo $this->formElementErrors($captcha);
    echo $formLabel->closeTag();
?></div>

<?= $this->formElement($form->get('security')) ?>
<?= $this->formElement($form->get('send')) ?>

<?= $this->form()->closeTag() ?>
```

There are a few things to note about this. First, to prevent confusion in IDEs
and editors when syntax highlighting, we use helpers to both open and close the
form and label tags. Second, there's a lot of repetition happening here; we
could easily create a partial view script or a composite helper to reduce
boilerplate. Third, note that not all elements are created equal &mdash; the
CSRF and submit elements don't need labels or error messages. Finally, note that
the `FormElement` helper tries to do the right thing &mdash; it delegates actual
markup generation to other view helpers. However, it can only guess what
specific form helper to delegate to based on the list it has. If you introduce
new form view helpers, you'll need to extend the `FormElement` helper, or create
your own.

Following the example above, your view files can quickly become long and
repetitive to write. While we do not currently provide a single-line form view
helper (as this reduces the form customization), we do provide convenience
wrappers around emitting individual elements via the `FormRow` view helper, and
collections of elements (`Laminas\Form\Element\Collection`, `Laminas\Form\Fieldset`, or
`Laminas\Form\Form`) via the `FormCollection` view helper (which, internally,
iterates the collection and calls `FormRow` for each element, recursively
following collections).

The `FormRow` view helper automatically renders a label (if present), the
element itself using the `FormElement` helper, as well as any errors that could
arise. Here is the previous form, rewritten to take advantage of this helper:

```php
<?php
// within a view script
$form = $this->form;
$form->prepare();

// Assuming the "contact/process" route exists...
$form->setAttribute('action', $this->url('contact/process'));

// Set the method attribute for the form
$form->setAttribute('method', 'post');

// Render the opening tag
echo $this->form()->openTag($form);
?>
<div class="form_element">
    <?= $this->formRow($form->get('name')) ?>
</div>

<div class="form_element">
    <?= $this->formRow($form->get('subject')) ?>
</div>

<div class="form_element">
    <?= $this->formRow($form->get('message')) ?>
</div>

<div class="form_element">
    <?= $this->formRow($form->get('captcha')) ?>
</div>

<?= $this->formElement($form->get('security')) ?>
<?= $this->formElement($form->get('send')) ?>

<?= $this->form()->closeTag() ?>
```

Note that `FormRow` helper automatically prepends the label. If you want it to
be rendered after the element itself, you can pass an optional parameter to the
`FormRow` view helper :

``` sourceCode
<div class="form_element">
    <?= $this->formRow($form->get('name'), 'append') ?>
</div>
```

As noted previously, the `FormCollection` view helper will iterate any
collection &mdash; including `Laminas\Form\Element\Collection`, fieldsets, and
forms &mdash; emitting each element discovered using `FormRow`. `FormCollection`
*does not render fieldset or form tags*; you will be responsible for emitting
those yourself.

The above examples can now be rewritten again:

```php
<?php
// within a view script
$form = $this->form;
$form->prepare();

// Assuming the "contact/process" route exists...
$form->setAttribute('action', $this->url('contact/process'));

// Set the method attribute for the form
$form->setAttribute('method', 'post');

// Render the opening tag
echo $this->form()->openTag($form);
echo $this->formCollection($form);
echo $this->form()->closeTag();
```

Finally, the `Form` view helper can optionally accept a `Laminas\Form\Form`
instance; if provided, it will prepare the form, iterate it, and render all
elements using either `FormRow` (for non-collection elements) or
`FormCollection` (for collections and fieldsets):

```php
<?php
// within a view script
$form = $this->form;

// Assuming the "contact/process" route exists...
$form->setAttribute('action', $this->url('contact/process'));

// Set the method attribute for the form
$form->setAttribute('method', 'post');

echo $this->form($form);
```

One important point to note about the last two examples: while they greatly
simplifies emitting the form, you also lose most customization opportunities.
The above, for example, will not include the `<div class="form_element"></div>`
wrappers from the previous examples! As such, you will generally want to use
this facility only when prototyping.

## Taking advantage of HTML5 input attributes

HTML5 brings a lot of exciting features, one of them being simplified client
form validations. laminas-form provides elements corresponding to the various HTML5
elements, specifying the client-side attributes required by them. Additionally,
each implements `InputProviderInterface`, ensuring that your input filter will
have reasonable default validation and filtering rules that mimic the
client-side validations.

> ### Always validate server-side
>
> Although client validation is nice from a user experience point of view, it
> must be used in addition to server-side validation, as client validation can
> be easily bypassed.

## Validation Groups

Sometimes you want to validate only a subset of form elements. As an example,
let's say we're re-using our contact form over a web service; in this case, the
`Csrf`, `Captcha`, and submit button elements are not of interest, and shouldn't
be validated.

laminas-form provides a proxy method to the underlying `InputFilter`'s
`setValidationGroup()` method, allowing us to perform this operation.

```php
$form->setValidationGroup(['name', 'email', 'subject', 'message']);
$form->setData($data);
if ($form->isValid()) {
    // Contains only the "name", "email", "subject", and "message" values
    $data = $form->getData();
}
```

If you later want to reset the form to validate all elements, call the
`FormInterface::setValidateAll()` method:

```php
use Laminas\Form\FormInterface;
$form->setValidateAll();
```

When your form contains nested fieldsets, you can use an array notation to
validate only a subset of the fieldsets :

```php
$form->setValidationGroup(['profile' => [
    'firstname',
    'lastname',
] ]);

$form->setData($data);
if ($form->isValid()) {
    // Contains only the "firstname" and "lastname" values from the
    // "profile" fieldset
    $data = $form->getData();
}
```

> ### You're not done
>
> In all likelihood, you'll need to add some more elements to the form you
> construct. For example, you'll want a submit button, and likely a
> CSRF-protection element. We recommend creating a fieldset with common elements
> such as these that you can then attach to the form you build via annotations.
