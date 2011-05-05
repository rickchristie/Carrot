Carrot: Simple, Experimental PHP Framework (Unstable)
-----------------------------------------------------

Carrot is an experimental framework that is created as a learning project. It quickly grows from a CodeIgniter-like framework into another beast of its own. It uses dependency injection container to instantiate all of the core classes and it uses anonymous functions heavily. Note that it's still very unstable, with many changes to the core coming.

Requirements
------------

- PHP 5.3+
- Apache web server (not tested in other web servers)

Framework design goals
----------------------

- Create a framework without using the keyword global or static. Fully recognize the dangerous nature of global state and avoid it at all costs.
- Relying on dependency injection container to manage the dependencies of user classes, thus eliminating the need for a global registry of object.
- Fully utilize PHP's new features by refusing to support outdated PHP installations. One of those features are anonymous functions, which are used extensively throughout the framework.
- Build and make use of decoupled classes, avoid inheritance whenever possible. This, for one, allows the user's controller class to be a plain old PHP object managed by the dependency injection container. You can even pick one of Carrot's core classes and use it as a standalone class.
- Make the core as small and focused as possible. Carrot's only job is to be the front controller, setting up the dependency injection container, instantiating the user's controller and getting a response from it. It does not know, much less dictate how the controller is getting the response.
- Allow the user to replace core classes of Carrot using their own class only by implementing an interface as a contract to the front controller, thereby creating an environment that does not disturb the user's programming routine.
- Continue the development by adding libraries, which are essentially just decoupled classes that are properly namespaced. Each library must not know that it is being used inside a framework.

Feedback
--------

The author welcomes any healthy criticisms and/or feedback. Send them all to [seven dot rchristie at gmail dot com](mailto:seven.rchristie@gmail.com).

Credits
-------

This framework uses a modifed version of [Fabien Potencier's sample dependency injection container](http://www.slideshare.net/fabpot/dependency-injection-with-php-53).

Special Thanks
--------------

[The people at Stack Overflow PHP chat](http://chat.stackoverflow.com/rooms/11/php), especially @Gordon, @edorian, @ircmaxell, @markus, @zerkms - for their help, criticisms, and guidance.