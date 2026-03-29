<template>
  <q-page padding>
    <div class="text-h5 text-weight-bold q-mb-lg flex items-center q-gutter-sm">
      <q-icon name="info" color="primary" size="sm" />
      <span>About</span>
    </div>

    <div class="row q-col-gutter-xl">
      <div class="col-12 col-md-8">
        <div class="text-h5 text-weight-bold q-mb-sm">👨‍💻 Author</div>
        <div class="text-h6 text-primary q-mb-md">Trần Vĩ Thành <span class="text-subtitle1 text-grey-7">— Freelance Web Developer & Edtech Builder</span></div>
        
        <q-banner class="bg-blue-1 text-blue-9 rounded-borders q-mb-lg">
          Providing web design and building operational systems for SMEs. Specialized in WordPress, WooCommerce, internal management systems and custom web solutions.
        </q-banner>

        <div class="text-h6 q-mb-sm">Plugin Information</div>
        <q-list dense class="q-mb-lg">
          <q-item>
            <q-item-section avatar><q-icon name="info" color="primary" /></q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-bold">Version: {{ pluginVersion }}</q-item-label>
              <q-item-label caption class="text-body2 text-grey-8 q-mt-xs" style="line-height:1.5;">
                TS License Manager is a robust, offline-first RSA-2048 licensing system for WordPress. It allows you to generate secure, unforgeable license keys that can be verified entirely locally within your client plugins or themes.
              </q-item-label>
            </q-item-section>
          </q-item>
        </q-list>

        <div class="text-h6 q-mb-sm">Contact Information</div>
        <q-list dense class="q-mb-lg">
          <q-item><q-item-section avatar><q-icon name="phone" color="grey-7" /></q-item-section><q-item-section><a href="tel:0949897293" class="text-primary" style="text-decoration:none">0949.897.293</a></q-item-section></q-item>
          <q-item><q-item-section avatar><q-icon name="email" color="grey-7" /></q-item-section><q-item-section><a href="mailto:thanh.web1001@gmail.com" class="text-primary" style="text-decoration:none">thanh.web1001@gmail.com</a></q-item-section></q-item>
          <q-item><q-item-section avatar><q-icon name="language" color="grey-7" /></q-item-section><q-item-section><a href="https://techsharevn.com" target="_blank" class="text-primary" style="text-decoration:none">techsharevn.com</a></q-item-section></q-item>
          <q-item><q-item-section avatar><q-icon name="description" color="grey-7" /></q-item-section><q-item-section><a href="https://techsharevn.com/my-cv" target="_blank" class="text-primary" style="text-decoration:none">techsharevn.com/my-cv</a></q-item-section></q-item>
          <q-item><q-item-section avatar><q-icon name="location_on" color="grey-7" /></q-item-section><q-item-section>47 Tan Hoa, Ward 14, Dist 6, HCMC, Vietnam</q-item-section></q-item>
        </q-list>

        <div class="text-h6 q-mb-sm">Experience</div>
        <ul class="q-pl-md q-mb-lg q-ml-sm">
          <li class="q-mb-sm"><strong>2016 – Present:</strong> Working at Dat Minh & Remote at SR Vietnam</li>
          <li><strong>Freelance:</strong> Website design, management system builder, digital marketing consulting</li>
        </ul>

        <div class="text-h6 q-mb-sm">Featured Projects</div>
        <q-markup-table flat bordered dense class="q-mb-lg">
          <thead>
            <tr class="bg-grey-2">
              <th class="text-left">Project</th>
              <th class="text-right">Link</th>
            </tr>
          </thead>
          <tbody>
            <tr><td class="text-left">WP Custom Orders</td><td class="text-right"><a href="https://techsharevn.com/project/custom-order-management-system-for-stickers-using-wordpress-and-woocommerce" target="_blank">View</a></td></tr>
            <tr><td class="text-left">Tim Việc Bảo Hiểm VN</td><td class="text-right"><a href="https://techsharevn.com/project/tim-viec-bao-hiem-vn" target="_blank">View</a></td></tr>
            <tr><td class="text-left">Q2 Legal</td><td class="text-right"><a href="https://techsharevn.com/project/q2-legal" target="_blank">View</a></td></tr>
            <tr><td class="text-left">Dongrealty.com</td><td class="text-right"><a href="https://techsharevn.com/project/dongrealty-com" target="_blank">View</a></td></tr>
            <tr><td class="text-left">Mind Connector VN</td><td class="text-right"><a href="https://techsharevn.com/project/mind-connector-vn" target="_blank">View</a></td></tr>
          </tbody>
        </q-markup-table>
      </div>
      
      <div class="col-12 col-md-4">
        <q-card flat bordered class="bg-grey-1">
          <q-card-section>
            <div class="text-h6 text-primary q-mb-md flex items-center q-gutter-sm">
              <q-icon name="local_cafe" size="sm" /> 
              <span>Support Project (Donate)</span>
            </div>
            <p class="text-body2 text-grey-9">If this plugin is useful and you want to support the author to continue developing, you can donate via:</p>
            
            <div class="text-weight-bold q-mt-md q-mb-sm">🏦 Vietcombank (VCB)</div>
            
            <div class="q-mb-md">
              <q-list dense bordered class="bg-white rounded-borders q-mb-md">
                <q-item><q-item-section>
                  <q-item-label caption>Account Holder</q-item-label>
                  <q-item-label class="text-weight-bold">Trần Vĩ Thành</q-item-label>
                </q-item-section></q-item>
                <q-item><q-item-section>
                  <q-item-label caption>Account Number</q-item-label>
                  <q-item-label class="text-primary text-weight-bold text-h6" style="letter-spacing: 1px;">0251 002 668 136</q-item-label>
                </q-item-section></q-item>
                <q-item><q-item-section>
                  <q-item-label caption>Transfer Content</q-item-label>
                  <q-item-label class="font-monospace text-grey-9">[Your Name] ung ho TS License Manager</q-item-label>
                </q-item-section></q-item>
              </q-list>
              
              <div class="text-center">
                <img :src="`https://img.vietqr.io/image/vcb-0251002668136-compact2.png?amount=200000&addInfo=Donate%20TS%20License%20Manager&accountName=Tran%20Vi%20Thanh`" 
                     alt="VietQR Donate" 
                     style="width: 100%; max-width: 180px; border-radius: 8px; border: 1px solid #e0e0e0; padding: 4px; background: white;" />
              </div>
            </div>

            <div class="text-caption text-grey-8 q-mt-md" style="font-style: italic; line-height: 1.5;">
              💙 Every small support helps the author to have more motivation to maintain and develop new features for the community. Thank you sincerely!
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { computed } from 'vue'

const pluginVersion = computed(() => window.tslmConfig?.version || '1.0.0')
</script>
