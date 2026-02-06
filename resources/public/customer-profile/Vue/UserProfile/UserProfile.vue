<template>
  <div v-if="loading" aria-live="polite" aria-hidden="true" class="border border-solid border-gray-outline rounded fct-customer-dashboard-layout-width">
    <el-skeleton class="px-5 py-5" :loading="loading" :rows="2" animated>
      <template #template>
        <div class="fct-form-container">
          <div class="form-left">
            <el-skeleton-item variant="p" class="w-[200px] mr-5"/>
            <el-skeleton-item variant="p" class="w-[240px] mr-5"/>
          </div>

          <div class="form-right">
            <el-skeleton-item variant="p" class="w-[200px]"/>
            <el-skeleton-item variant="p" class="w-[300px]"/>
          </div>
        </div>
        <div class="fct-form-container">
          <div class="form-left">
            <el-skeleton-item variant="p" class="w-[200px] mr-5"/>
            <el-skeleton-item variant="p" class="w-[240px] mr-5"/>
          </div>

          <div class="form-right">
            <el-skeleton-item variant="p" class="w-[200px]"/>
            <el-skeleton-item variant="p" class="w-[300px]"/>
          </div>
        </div>
        <div class="fct-form-container">
          <div class="form-left">
            <el-skeleton-item variant="p" class="w-[200px] mr-5"/>
            <el-skeleton-item variant="p" class="w-[240px] mr-5"/>
          </div>

          <div class="form-right">
            <el-skeleton-item variant="p" class="w-[200px]"/>
            <el-skeleton-item variant="p" class="w-[300px]"/>
          </div>
        </div>
      </template>
    </el-skeleton>
  </div>

  <div v-if="profileDetails && !loading" class="fct-customer-dashboard-user-profile fct-customer-dashboard-layout-width" aria-live="polite">
    <nav class="fct-profile-tabs" :aria-label="translate('Profile sections')">
      <button
          type="button"
          class="fct-profile-tabs__item"
          :class="{ 'is-active': activeTab === 'profile' }"
          @click="setActiveTab('profile')"
      >
        {{ translate('Profile') }}
      </button>
      <button
          type="button"
          class="fct-profile-tabs__item"
          :class="{ 'is-active': activeTab === 'support-centre' }"
          @click="setActiveTab('support-centre')"
      >
        {{ translate('Support Centre') }}
      </button>
    </nav>

    <template v-if="activeTab === 'profile'">
      <AccountDetails :profileDetails="profileDetails"/>

      <template v-if="!profileDetails.not_a_customer">
      <!--    Billing Address-->
      <BillingAddress :profileDetails="profileDetails"
                      @fetch="fetch"
                      @setAddressPrimary="setAddressPrimary"
                      @deleteAddress="deleteAddress"/>

      <!--    Shipping Address-->
      <ShippingAddress :profileDetails="profileDetails"
                      @fetch="fetch"
                      @setAddressPrimary="setAddressPrimary"
                      @deleteAddress="deleteAddress"/>
    
      <!--    Save button disabling now -->
      <footer class="fct-form-footer text-right">
        <el-button
            type="primary"
            size="small"
            @click="updateCustomerProfile"
            :disabled="!isProfileChanged"
            :aria-label="translate('Save Profile')"
        >
          {{ translate('Save Profile') }}
        </el-button>
      </footer>
      </template>
    </template>

    <section v-else class="fct-support-centre" role="region" :aria-label="translate('Support Centre')">
      <div class="fct-form-container fct-support-centre__container">
        <div class="form-left">
          <h3 class="form-heading">{{ translate('Support Centre') }}</h3>
          <p class="form-desc">{{ translate('Submit and manage your support tickets from your customer account.') }}</p>
        </div>
        <div class="form-right">
          <div class="fct-support-centre__portal" v-html="supportPortalHtml" />
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import DynamicIcon from "@/Bits/Components/Icons/DynamicIcon.vue";
import Badge from "@/Bits/Components/Badge.vue";
import Notify from "@/utils/Notify";
import MaterialInput from "@/Bits/Components/MaterialInput.vue";
import AddressComponent from "@/Bits/Components/Address/AddressComponent.vue";
import translate from "../../translator/Translator";

import AccountDetails from "./templates/AccountDetails.vue";
import BillingAddress from "./templates/BillingAddress.vue";
import ShippingAddress from "./templates/ShippingAddress.vue"


export default {
  name: 'UserProfile',
  components: {
    DynamicIcon,
    Badge,
    MaterialInput,
    AddressComponent,
    AccountDetails,
    BillingAddress,
    ShippingAddress,
  },
  data() {
    return {
      validationErrors: {},
      isProfileChanged: false,
      originalProfileDetails: {},
      loading: false,
      addAddressLoading: false,
      profileDetails: null,
      billingAddress: null,
      shippingAddress: null,
      addNewAddressModalVisible: false,
      isEditAddress: false,
      modalAction: 'billing',
      newEmail: '',
      emailConfirmationData: {},
      emailChangeTokenExpiry: '',
      activeTab: 'profile',
      supportPortalHtml: window.fluentcart_customer_profile_vars?.support_portal_html || '',
    };
  },
  watch: {
    'profileDetails.first_name': {
      handler() {
        this.checkProfileChanges();
      }
    },
    'profileDetails.last_name': {
      handler() {
        this.checkProfileChanges();
      }
    },
    '$route'(to) {
      this.activeTab = to?.meta?.profile_tab === 'support-centre' ? 'support-centre' : 'profile';
    }
  },
  methods: {
    translate,
    updateCustomerProfile () {
      this.loading = true;
      this.$post("customer-profile/update", {
        first_name: this.profileDetails.first_name,
        last_name: this.profileDetails.last_name,
        email: this.profileDetails.email,
      })
          .then(response => {
            this.isProfileChanged = false;
            Notify.success(response.message);
            this.fetch();
          })
          .catch(error => {
            Notify.error(error);
          })
          .finally(() => {
            this.loading = false;
          });
    },
    setAddressPrimary(address) {
        let completeAddress = address;
        let customer_id = completeAddress.customer_id;

        this.$post(`customer-profile/make-primary-address`, {
          addressId: completeAddress.id,
          type: completeAddress.type
        }).then(response => {
          Notify.success(response);
          this.fetch();
        }).catch((errors) => {
          Notify.success(errors);
        });

    },
    deleteAddress(address) {
        let completeAddress = address;
        let customer_id = completeAddress.customer_id;
        this.$post(`customer-profile/delete-address`, {
          addressId: completeAddress.id
        }).then(response => {
          Notify.success(response);
          this.fetch();
        }).catch((errors) => {
          Notify.error(errors);
        });

    },
    setActiveTab(tab) {
      this.activeTab = tab;
      const routeName = tab === 'support-centre' ? 'support-centre' : 'profile';
      if (this.$route?.name !== routeName) {
        this.$router.push({ name: routeName });
      }
    },
    fetch() {
        this.loading = true;

        this.$get("customer-profile/profile")
            .then((response) => {
              this.originalProfileDetails = JSON.parse(JSON.stringify(response.data));
              this.profileDetails = response?.data;
              this.newEmail = response?.data?.new_email;
              this.emailChangeTokenExpiry = response?.data?.email_change_token_expiry;
              this.isProfileChanged = false;
            })
            .finally(() => {
              this.loading = false;
            });

    },
    editAddress() {
      if (this.validateForm()) {
        this.addAddressLoading = true;
        let completeAddress = {...this.newAddressData};
        let customer_id = completeAddress.customer_id;

        this.$post(`customer-profile/edit-address`, {
          id: completeAddress.id,
          label: completeAddress.label,
          name: completeAddress.name,
          phone: completeAddress.phone,
          email: completeAddress.email,
          country: completeAddress.country,
          state: completeAddress.state,
          address_1: completeAddress.address_1,
          address_2: completeAddress.address_2,
          city: completeAddress.city,
          postcode: completeAddress.postcode,
          type: completeAddress.type
        }).then(response => {
          Notify.success(response);
          this.fetch();
          this.addAddressLoading = false;
        }).catch((errors) => {
          Notify.error(errors);
        }).finally(() => {
          this.addNewAddressModalVisible = false;
          this.resetFormDataAndValidation();
        });
      } else {
        Notify.error(translate('Please fill in all the required fields.'));
      }
    },
    checkProfileChanges() {
      if (this.profileDetails && this.originalProfileDetails && Object.keys(this.originalProfileDetails).length > 0) {
        const isFirstNameChanged = this.profileDetails.first_name !== this.originalProfileDetails.first_name;
        const isLastNameChanged = this.profileDetails.last_name !== this.originalProfileDetails.last_name;
        this.isProfileChanged = isFirstNameChanged || isLastNameChanged;
      }
    },
  },
  mounted() {
    this.activeTab = this.$route?.meta?.profile_tab === 'support-centre' ? 'support-centre' : 'profile';
    this.fetch();
  },
};
</script>

