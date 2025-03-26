# ServiceProvider

<!-- @doc-source: ServiceProvider -->


## Methods

### register
<!-- @doc-source: ServiceProvider.register -->
ServiceProvider Interface
Defines the contract for all service providers
/

namespace FastyChild\Core;

/**
Interface for service providers
/
interface ServiceProvider
{
/**
Register services in the container

#### Returns



### boot
<!-- @doc-source: ServiceProvider.boot -->
Boot services after all providers are registered

#### Returns



### provides
<!-- @doc-source: ServiceProvider.provides -->
Get the services provided by the provider
Used for deferred loading

#### Returns



