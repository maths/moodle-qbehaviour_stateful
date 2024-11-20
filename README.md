# Stateful question behaviour 1.0

Stateful is a question type based on [STACK](https://github.com/maths/moodle-qtype_stack/) like STACK its well suited for assessment of STEM subjects. However, the thing that distinguishes it from STACK also opens up usage in other fields.

The point of Stateful is to provide memory within the question logic by using state-variables. Thus making it possible to modify the parameters of the question based on inputs received while the student is interacting with it.

Stateful was created by [Matti Harjula](http://math.aalto.fi/en/people/matti.harjula) of the Aalto University. Stateful, contains contributions done by Eleaga Oy Ltd, it has also been supported by funding from the Finnish Ministry of Education and Culture.

### Changelog

**1.0.1** PHP changes for the initial step.


## Current state of development

The behaviour portion (i.e. this repository) of Stateful has reached minimal requirements, it provides the question type question attempt level state storage.

Later stages aim for extending to memory stored at user level with various annealing logics to provide ways for questions to affect later questions. The memory annealing logic will necessarily bring with it tools to access, modify, and analyse that memory. It is expected that such variable will always be scalar and that they may include some form of Fuzzy logic styled behaviour.


## License

Stateful question type and Stateful question behaviour is Licensed under the GNU General Public, License Version 3. The licensing of various Stateful-editors is up to those editors.
