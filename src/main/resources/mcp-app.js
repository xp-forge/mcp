class McpApp {
  #nextId = 1;
  #pending = {};
  #handlers = {
    'ui/notifications/host-context-changed': params => this.#apply(params.styles),
  };

  /** Creates a new MCP app */
  constructor(name, version= '1.0.0') {
    this.name = name;
    this.version = version;

    // Match events to pending promises
    window.addEventListener('message', (event) => {
      if ('2.0' !== event.data?.jsonrpc) return;

      if ('result' in event.data) {
        const promise = this.#pending[event.data.id] ?? null;
        if (event.data?.result) {
          promise.resolve(event.data.result);
        } else if (event.data?.error) {
          promise.reject(new Error(event.data.error.message));
        } else {
          promise.reject(new Error(`Unsupported message: ${JSON.stringify(event.data)}`));
        }
      } else if (event.data.method in this.#handlers) {
        this.#handlers[event.data.method](event.data.params);
      } else {
        console.log('Unhandled', event.data);
      }
    });
  }

  #apply(styles) {
    const $root = document.documentElement.style;
    for (const [property, value] of Object.entries(styles?.variables)) {
      value === undefined || $root.setProperty(property, value);
    }
  }

  async #send(method, params) {
    const id = this.#nextId++;

    this.#pending[id] = Promise.withResolvers();
    window.parent.postMessage({ jsonrpc: '2.0', id, method, params }, '*');

    return this.#pending[id].promise;
  }

  /** Adds an event handler for a given message type, e.g. `ui/notifications/tool-result` */
  on(event, handler) {
    this.#handlers[event] = handler;
  }

  /** Initialize the app using the initialize/initialized handshake */
  async initialize() {
    const result = await this.#send('ui/initialize', {
      appCapabilities: {},
      appInfo: {name: this.name, version: this.version},
      protocolVersion: '2026-01-26',
    });
    this.#apply(result.hostContext.styles);

    window.parent.postMessage({ jsonrpc: '2.0', method: 'ui/notifications/initialized' }, '*');
    return Promise.resolve(result);
  }

  /** Send message content to the host's chat interface */
  async send(text) {
    return this.#send('ui/message', { role: 'user', content: [{ type: 'text', text }] });
  }

  /** Tells host to open a given link */
  async open(link) {
    return this.#send('ui/open-link', { url: link });
  }

  /** Makes host proxy an MCP tool call and return its result */
  async call(tool, args) {
    return this.#send('tools/call', { name: tool, arguments: args });
  }
}